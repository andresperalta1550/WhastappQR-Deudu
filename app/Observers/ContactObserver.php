<?php

namespace App\Observers;

use App\Events\ContactCreated as ContactCreatedEvent;
use App\Events\ContactUpdated as ContactUpdatedEvent;
use App\Models\Channel;
use App\Models\Contact;
use App\Models\Debtor;
use Illuminate\Support\Facades\Log;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        Log::debug('[ContactObserver] Contact created', [
            'contact_id' => $contact->getId(),
            'remote_phone_number' => $contact->getRemotePhoneNumber(),
        ]);

        // Broadcast the ContactCreated event via WebSocket
        // Get the debtor id from the contact
        $debtorId = $contact->getDebtorId();
        // Get the channel by phone number
        $channel = Channel::getChannelByPhoneNumber($contact->getChannelPhoneNumber());

        // Get the coordination id from the channel
        $userId = $channel->getCoordinationId();
        if ($debtorId) {
            $userId = (new Debtor())->find($debtorId)->getAnalyst();
            broadcast(new ContactCreatedEvent($contact, $userId));
        }

        broadcast(new ContactCreatedEvent($contact, $userId));
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        // Broadcast the ContactCreated event via WebSocket
        // Get the debtor id from the contact
        // Broadcast the ContactCreated event via WebSocket
        // Get the debtor id from the contact
        $debtorId = $contact->getDebtorId();
        // Get the channel by phone number
        $channel = Channel::getChannelByPhoneNumber($contact->getChannelPhoneNumber());

        // Get the coordination id from the channel
        $userId = $channel->getCoordinationId();
        if ($debtorId) {
            $userId = (new Debtor())->find($debtorId)->getAnalyst();
            broadcast(new ContactUpdatedEvent($contact, $userId));
        }

        broadcast(new ContactUpdatedEvent($contact, $userId));
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $contact): void
    {
        //
    }

    /**
     * Handle the Contact "restored" event.
     */
    public function restored(Contact $contact): void
    {
        //
    }

    /**
     * Handle the Contact "force deleted" event.
     */
    public function forceDeleted(Contact $contact): void
    {
        //
    }
}
