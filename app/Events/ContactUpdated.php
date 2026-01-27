<?php

namespace App\Events;

use App\Models\Contact;
use App\Models\Debtor;
use App\Models\Channel as ChannelModel;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Contact $contact,
        public ?int $userId
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('contacts.' . $this->userId ?? '1'),
            new Channel('contacts'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $channel = ChannelModel::getChannelByPhoneNumber($this->contact->getChannelPhoneNumber());
        if (!$channel && !$this->contact->getDebtorId()) {
            return $this->contact->toArray();
        }
        $debtor = Debtor::find($this->contact->getDebtorId());
        $contact = $this->contact->toArray();
        if ($debtor) {
            $contact['debtor_fullname'] = $debtor->getFullname();
            $contact['debtor_identification'] = $debtor->getIdentification();
        }

        return $contact;
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'contact.updated';
    }
}
