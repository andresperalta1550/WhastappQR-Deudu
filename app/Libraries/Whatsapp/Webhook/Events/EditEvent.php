<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use \App\ValueObjects\Edited;
use \App\ValueObjects\PreviousVersion;
use Carbon\Carbon;
use \App\Models\Message;

class EditEvent extends WebhookEvent
{
    /**
     * @var string $uuid The UUID of the message.
     */
    public string $uuid;

    /**
     * @var array $message The message data.
     */
    public array $message;

    public function process(): void
    {
        // Search the message by uuid
        $message = (new Message())
            ->where("message_uuid", $this->uuid)
            ->first();

        // If the message is not found, return
        if (!$message) {
            return;
        }

        // Get the edited object
        $edited = $message->getEdited();
        if (!$edited) {
            $edited = new Edited();
        }

        // Set the edited object
        $edited->setIsEdited(true);
        $edited->setEditedAt(Carbon::parse($this->timestamp, 'UTC'));
        $prev = $edited->getPreviousVersions();
        $prev[] = new PreviousVersion(
            $message->getText(),
            Carbon::parse($this->timestamp, 'UTC')
        );
        $edited->setPreviousVersions($prev);
        // Set the new text
        $message->setText($this->message['text']);
        // Set the edited object
        $message->setEdited($edited);
        // Save the message
        $message->save();
    }
}
