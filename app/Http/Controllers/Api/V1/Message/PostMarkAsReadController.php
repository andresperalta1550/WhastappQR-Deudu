<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;

class PostMarkAsReadController extends Controller
{
    public function __invoke(int $debtorId)
    {
        $contact = (new Contact())
            ->where('debtor_id', $debtorId)
            ->first();

        $messages = (new Message())
            ->where('debtor_id', $debtorId)
            ->get();

        foreach ($messages as $message) {
            $message->markAsRead();
            $message->save();
        }

        if ($contact) {
            $contact->setUnreadMessages(0);
            $contact->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensajes marcados como leidos',
        ]);
    }
}
