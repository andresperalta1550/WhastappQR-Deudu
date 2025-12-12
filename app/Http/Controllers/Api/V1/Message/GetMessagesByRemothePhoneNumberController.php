<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use Illuminate\Support\Facades\Log;
use App\Models\Message;

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

        foreach ($messages as $message) {
            $message->setInternalRead(true);
            $message->setInternalReadAt(\Carbon\Carbon::now());
            $message->save();
        }

        $this->updateUnreadMessagesCount($remotePhoneNumber);

        return response()->json([
            'success' => true,
            'data' => $messages,
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
