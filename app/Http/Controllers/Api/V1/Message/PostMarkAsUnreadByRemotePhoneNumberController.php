<?php

namespace App\Http\Controllers\Api\V1\Message;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;

class PostMarkAsUnreadByRemotePhoneNumberController extends Controller
{
    public function __invoke(string $remotePhoneNumber)
    {
        $contact = (new Contact())
            ->where('remote_phone_number', $remotePhoneNumber)
            ->first();

        $message = (new Message())
            ->where('remote_phone_number', $remotePhoneNumber)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$contact || !$message) {
            throw new NotFoundException('El contacto no fue encontrado.');
        }

        $contact->setUnreadMessages(1);
        $contact->save();

        $message->markAsUnread();
        $message->save();

        return response()->json([
            'success' => true,
            'message' => 'Mensajes marcados como no leidos',
        ]);
    }
}
