<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use App\Models\Message;
use App\ValueObjects\Reaction;

class ReactionEvent extends WebhookEvent
{
    /**
     * @var string $reaction The reaction emoji.
     */
    public string $reaction;

    /**
     * @var array $message The message data.
     */
    public array $message;

    public function process(): void
    {
        // Search the message by uuid
        $message = (new Message())
            ->where("message_uuid", $this->message['uuid'])
            ->first();

        // If the message is not found, return
        if (!$message)
            return;

        // Add the reaction to the message
        $reactions = $message->getReactions();
        $reactions[] = new Reaction(
            $this->reaction,
            $this->message['remote_phone_number'],
            \Carbon\Carbon::parse($this->timestamp),
        );
        $message->setReactions($reactions);
        $message->save();
    }
}