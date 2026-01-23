<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Contact;

class PostMarkAsUnreadByDebtorController extends Controller
{
    public function __invoke(int $debtorId)
    {
        $contact = (new Contact())
            ->where('debtor_id', $debtorId)
            ->first();

        $message = (new Message())
            ->where('debtor_id', $debtorId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($message) {
            $message->markAsUnread();
            $message->save();
        }

        if ($contact) {
            $contact->setUnreadMessages(1);
            $contact->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensajes marcados como no leidos',
        ]);
    }
}
