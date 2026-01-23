<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message,
        public ?int $debtorId,
        public ?string $remotePhoneNumber
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
        if ($this->debtorId) {
            return [
                new Channel('messages.by_debtor.' . $this->debtorId),
            ];
        }
        return [
            new Channel('messages.by_remote_phone_number.' . $this->remotePhoneNumber),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $user = User::find($this->message->getSentUserBy());
        if (!$user) {
            return $this->message->toArray();
        }
        $message = $this->message->toArray();
        $message['sent_user_by_fullname'] = $user->getFullname();
        return $message;
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}
