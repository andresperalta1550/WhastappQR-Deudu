<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use Carbon\Carbon;

abstract class WebhookEvent
{
    /**
     * @var string $eventType The type of the event
     */
    public string $eventType;

    /**
     * @var string $channelUuid The UUID of the channel
     */
    public string $channelUuid;

    /**
     * @var string $timestamp The timestamp of the event
     */
    public string $timestamp;

    /**
     * Process the webhook event.
     *
     * @return void
     */
    public abstract function process(): void;

    /**
     * Convert the webhook event to an array
     *
     * @return array The webhook event as an array
     */
    public function toArray(): array
    {
        return [
            'event_type' => $this->eventType,
            'channel_uuid' => $this->channelUuid,
            'timestamp' => $this->timestamp
        ];
    }
}
