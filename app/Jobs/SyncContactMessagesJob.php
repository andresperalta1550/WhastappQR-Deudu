<?php

namespace App\Jobs;

use App\Libraries\Whatsapp\Client as WhatsappClient;
use App\Models\Contact;
use App\Models\Message;
use App\ValueObjects\Delivery;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync messages for a specific WhatsApp conversation.
 *
 * This job is dispatched transparently whenever a user opens a conversation.
 * It fetches the full message history from the 2Chat API and inserts any
 * messages that are not yet stored in the local database, compensating for
 * webhook failures.
 *
 * The job is unique per channel+remote pair for 5 minutes to prevent
 * redundant API calls when multiple requests arrive for the same conversation.
 */
class SyncContactMessagesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job should be unique.
     * Prevents duplicate syncs for the same conversation pair.
     */
    public int $uniqueFor = 300; // 5 minutes

    /**
     * Constructor.
     *
     * @param string  $channelPhoneNumber  Channel phone number (e.g. +573224816062)
     * @param string  $remotePhoneNumber   Contact phone number (e.g. +573224220769)
     * @param Contact $contact             The contact model to update after sync
     */
    public function __construct(
        protected string $channelPhoneNumber,
        protected string $remotePhoneNumber,
        protected Contact $contact,
    ) {
        Log::debug('[SyncContactMessagesJob] Job created', [
            'channel_phone_number' => $channelPhoneNumber,
            'remote_phone_number'  => $remotePhoneNumber,
            'contact_id'           => $contact->getId(),
        ]);
    }

    /**
     * Unique job ID — one sync per conversation pair at a time.
     */
    public function uniqueId(): string
    {
        return 'sync-contact-messages-' . $this->channelPhoneNumber . '-' . $this->remotePhoneNumber;
    }

    /**
     * Execute the sync job.
     */
    public function handle(): void
    {
        Log::debug('[SyncContactMessagesJob] Starting sync', [
            'channel_phone_number' => $this->channelPhoneNumber,
            'remote_phone_number'  => $this->remotePhoneNumber,
        ]);

        try {
            // 1. Fetch messages from 2Chat API
            $client   = new WhatsappClient();
            $response = $client->getMessages($this->channelPhoneNumber, $this->remotePhoneNumber);

            $apiMessages = $response['messages'] ?? [];

            if (empty($apiMessages)) {
                Log::info('[SyncContactMessagesJob] No messages returned from API', [
                    'channel_phone_number' => $this->channelPhoneNumber,
                    'remote_phone_number'  => $this->remotePhoneNumber,
                ]);

                $this->updateContactSyncTimestamp();
                return;
            }

            Log::debug('[SyncContactMessagesJob] API returned messages', [
                'count' => count($apiMessages),
            ]);

            // 2. Build an O(1) lookup set of existing message UUIDs in the DB
            //    for this specific conversation (channel + remote pair).
            $existingUuids = Message::query()
                ->where('channel_phone_number', $this->channelPhoneNumber)
                ->where('remote_phone_number', $this->remotePhoneNumber)
                ->pluck('message_uuid')
                ->flip() // Convert to [uuid => index] for O(1) isset() checks
                ->all();

            // 3. Iterate API messages and insert only those missing locally
            $created = 0;
            $skipped = 0;

            foreach ($apiMessages as $apiMsg) {
                $uuid = $apiMsg['uuid'] ?? null;

                if ($uuid === null) {
                    $skipped++;
                    continue;
                }

                // O(1) check — no additional DB query per message
                if (isset($existingUuids[$uuid])) {
                    $skipped++;
                    continue;
                }

                try {
                    $this->insertMessage($apiMsg);
                    $created++;
                } catch (\Throwable $e) {
                    Log::warning('[SyncContactMessagesJob] Failed to insert message', [
                        'uuid'  => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                    $skipped++;
                }
            }

            Log::info('[SyncContactMessagesJob] Sync complete', [
                'channel_phone_number' => $this->channelPhoneNumber,
                'remote_phone_number'  => $this->remotePhoneNumber,
                'created'              => $created,
                'skipped'              => $skipped,
                'total'                => count($apiMessages),
            ]);

        } catch (\Throwable $e) {
            Log::error('[SyncContactMessagesJob] Sync failed', [
                'channel_phone_number' => $this->channelPhoneNumber,
                'remote_phone_number'  => $this->remotePhoneNumber,
                'error'                => $e->getMessage(),
                'trace'                => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            // Always update the timestamp, even if the sync partially failed,
            // to avoid flooding the API with retries on transient errors.
            $this->updateContactSyncTimestamp();
        }
    }

    /**
     * Map an API message payload to a Message model and persist it.
     *
     * @param array $apiMsg Raw message object from 2Chat API
     */
    private function insertMessage(array $apiMsg): void
    {
        $sentBy    = $apiMsg['sent_by'] ?? null;
        $timestamp = $apiMsg['created_at'] ?? null;
        $parsedAt  = $timestamp ? Carbon::parse($timestamp, 'UTC') : null;

        // Determine message type: the API nests the content under the type key
        $messageContent = $apiMsg['message'] ?? [];
        $type           = is_array($messageContent) ? array_key_last($messageContent) : null;
        $text           = is_array($messageContent) ? ($messageContent['text'] ?? null) : null;

        $model = new Message();
        $model->setMessageUuid($apiMsg['uuid']);
        $model->setSessionKey($apiMsg['session_key'] ?? null);
        $model->setChannelPhoneNumber($this->channelPhoneNumber);
        $model->setRemotePhoneNumber($this->remotePhoneNumber);
        $model->setDirection($sentBy === 'user' ? 'inbound' : 'outbound');
        $model->setSentBy($sentBy);
        $model->setSource('sync'); // Marks messages inserted via sync, not webhook
        $model->setSentUserBy(null);
        $model->setType($type);
        $model->setText($text);
        $model->setStatus('delivered');
        $model->setDelivery(new Delivery(
            sentAt:      $parsedAt,
            deliveredAt: $parsedAt,
            readAt:      null,
        ));
        $model->setInternalRead(false);
        $model->setInternalReadAt(null);
        $model->setRawPayload($apiMsg);

        // Assign debtor_id from the contact if already resolved
        $model->setDebtorId($this->contact->getDebtorId());

        $model->saveQuietly();
    }

    /**
     * Update the contact's last_synced_at timestamp.
     */
    private function updateContactSyncTimestamp(): void
    {
        try {
            $this->contact->setLastSyncedAt(now());
            $this->contact->save();
        } catch (\Throwable $e) {
            Log::warning('[SyncContactMessagesJob] Failed to update last_synced_at', [
                'contact_id' => $this->contact->getId(),
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
