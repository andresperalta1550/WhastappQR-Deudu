<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Jobs\SyncContactMessagesJob;
use App\Models\Contact;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetMessagesByDebtorController extends Controller
{
    public function __invoke(
        int $debtorId,
        Request $request
    ): \Illuminate\Http\JsonResponse {
        $perPage = 10; // Number of messages per page
        $page = $request->get('page', 1);

        // We paginate the messages for better performance
        $query = Message::query()
            ->where('debtor_id', $debtorId)
            ->orderBy('delivery.sent_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $messages = collect($paginator->items());

        $users_ids = $messages->pluck('sent_user_by')->unique()->toArray();

        $users = User::whereIn('id', $users_ids)->get();

        // We group based on changes in remote_phone_number or channel_phone_number
        $groups = [];
        $currentGroup = [];
        $lastRemotePhoneNumber = null;
        $lastChannelPhoneNumber = null;

        foreach ($messages as $message) {
            if (
                $message->getRemotePhoneNumber() !== $lastRemotePhoneNumber ||
                $message->getChannelPhoneNumber() !== $lastChannelPhoneNumber
            ) {
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [];
            }
            // Set last seen values
            $message->setInternalRead(true);
            $message->setInternalReadAt(\Carbon\Carbon::now());

            $messageResponse = $message->toArray();
            $user = $users->where('id', $message->getSentUserBy())->first();
            $messageResponse['sent_user_by_fullname'] = null;
            if ($user) {
                $messageResponse['sent_user_by_fullname'] = $user->getFullName();
            }

            $currentGroup[] = $messageResponse;
            $message->save();
            $lastRemotePhoneNumber = $message->getRemotePhoneNumber();
            $lastChannelPhoneNumber = $message->getChannelPhoneNumber();

        }

        $this->updateUnreadMessagesCount($debtorId);

        // Dispatch a background sync to fill any messages missed by webhook failures.
        $this->dispatchSyncIfNeeded($debtorId);

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return response()->json([
            'success' => true,
            'data' => $groups,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Dispatch a background sync job for contacts linked to a debtor,
     * covering all unique channel+remote pairs that debtor has.
     *
     * @param int $debtorId
     * @return void
     */
    private function dispatchSyncIfNeeded(int $debtorId): void
    {
        $contact = Contact::where('debtor_id', $debtorId)->first();

        if (!$contact || !$contact->getChannelPhoneNumber()) {
            return;
        }

        $intervalMinutes = (int) config('services.whatsapp.sync_interval_minutes', 10);
        $lastSync        = $contact->getLastSyncedAt();
        $shouldSync      = $lastSync === null
            || $lastSync->lt(now()->subMinutes($intervalMinutes));

        if (!$shouldSync) {
            Log::debug('[GetMessagesByDebtorController] Sync skipped (recently synced)', [
                'debtor_id'      => $debtorId,
                'last_synced_at' => $lastSync?->toIso8601String(),
            ]);
            return;
        }

        Log::debug('[GetMessagesByDebtorController] Dispatching SyncContactMessagesJob', [
            'debtor_id'            => $debtorId,
            'channel_phone_number' => $contact->getChannelPhoneNumber(),
            'remote_phone_number'  => $contact->getRemotePhoneNumber(),
        ]);

        SyncContactMessagesJob::dispatch(
            $contact->getChannelPhoneNumber(),
            $contact->getRemotePhoneNumber(),
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
        int $debtorId
    ): void {
        // Search the contact
        $contact = (new \App\Models\Contact())
            ->where('debtor_id', $debtorId)
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

