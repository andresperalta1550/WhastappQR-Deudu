<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message
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
            new Channel('messages'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->getId(),
                'message_uuid' => $this->message->getMessageUuid(),
                'channel_uuid' => $this->message->getChannelUuid(),
                'remote_phone_number' => $this->message->getRemotePhoneNumber(),
                'debtor_id' => $this->message->getDebtorId(),
                'direction' => $this->message->getDirection(),
                'type' => $this->message->getType(),
                'text' => $this->message->getText(),
                'status' => $this->message->getStatus(),
            ],
        ];
    }
}
