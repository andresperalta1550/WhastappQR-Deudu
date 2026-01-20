<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
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

