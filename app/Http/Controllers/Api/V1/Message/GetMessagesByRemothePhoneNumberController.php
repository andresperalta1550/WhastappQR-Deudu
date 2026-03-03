<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Jobs\SyncContactMessagesJob;
use App\Models\Contact;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GetMessagesByRemothePhoneNumberController extends Controller
{
    public function __invoke(
        string $remotePhoneNumber,
        PaginationRequest $request
    ): \Illuminate\Http\JsonResponse {
        $perPage = $request->getPerPage();
        $page = $request->getPage();

        if (!str_contains($remotePhoneNumber, '+')) {
            $remotePhoneNumber = '+' . $remotePhoneNumber;
        }

        $query = (new Message())
            ->where('remote_phone_number', $remotePhoneNumber)
            ->orderBy('delivery.sent_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $messages = collect($paginator->items());

        $users_ids = $messages->pluck('sent_user_by')->unique()->toArray();

        $users = User::whereIn('id', $users_ids)->get();

        $messagesResponse = [];

        foreach ($messages as $message) {
            $message->setInternalRead(true);
            $message->setInternalReadAt(\Carbon\Carbon::now());
            $message->save();
            $messageResponse = $message->toArray();
            $user = $users->where('id', $message->getSentUserBy())->first();
            $messageResponse['sent_user_by_fullname'] = null;
            if ($user) {
                $messageResponse['sent_user_by_fullname'] = $user->getFullName();
            }
            $messagesResponse[] = $messageResponse;
        }

        $this->updateUnreadMessagesCount($remotePhoneNumber);

        // Dispatch a background sync to fill any messages missed by webhook failures.
        // The job is unique per conversation pair and rate-limited by last_synced_at,
        // so it runs at most once every N minutes (default: 10).
        $this->dispatchSyncIfNeeded($remotePhoneNumber);

        return response()->json([
            'success' => true,
            'data' => $messagesResponse,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Dispatch a background sync job for this conversation if enough time
     * has passed since the last sync.
     *
     * @param string $remotePhoneNumber
     * @return void
     */
    private function dispatchSyncIfNeeded(string $remotePhoneNumber): void
    {
        $contact = Contact::where('remote_phone_number', $remotePhoneNumber)->first();

        if (!$contact || !$contact->getChannelPhoneNumber()) {
            return;
        }

        $intervalMinutes = (int) config('services.whatsapp.sync_interval_minutes', 10);
        $lastSync        = $contact->getLastSyncedAt();
        $shouldSync      = $lastSync === null
            || $lastSync->lt(now()->subMinutes($intervalMinutes));

        if (!$shouldSync) {
            Log::debug('[GetMessagesByRemothePhoneNumberController] Sync skipped (recently synced)', [
                'remote_phone_number' => $remotePhoneNumber,
                'last_synced_at'      => $lastSync?->toIso8601String(),
            ]);
            return;
        }

        Log::debug('[GetMessagesByRemothePhoneNumberController] Dispatching SyncContactMessagesJob', [
            'remote_phone_number'  => $remotePhoneNumber,
            'channel_phone_number' => $contact->getChannelPhoneNumber(),
        ]);

        SyncContactMessagesJob::dispatch(
            $contact->getChannelPhoneNumber(),
            $remotePhoneNumber,
            $contact
        );
    }

    /**
     * Update the unread messages count to zero for the contact.
     *
     * @param int $debtorId
     * @return void
     */
    private function updateUnreadMessagesCount(
        string $remotePhoneNumber
    ): void {
        // Search the contact
        $contact = (new \App\Models\Contact())
            ->where('remote_phone_number', $remotePhoneNumber)
            ->first();

        // If contact is found, increment the inbound message count
        if ($contact) {
            $contact->setUnreadMessages(0);
            $contact->save();

            Log::debug('[MessageCreatedObserver] Contact updated successfully', [
                'contact_id' => $contact->getId(),
                'unread_messages' => $contact->getUnreadMessages()
            ]);
        }
    }
}
