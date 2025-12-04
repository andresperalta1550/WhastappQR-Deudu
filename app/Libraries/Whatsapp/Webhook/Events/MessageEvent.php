<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use App\Libraries\Whatsapp\Webhook\MessageHandlerFactory;

class MessageEvent extends WebhookEvent
{
    /**
     * @var string $uuid The UUID of the message.
     */
    public string $uuid;

    /**
     * @var string $sessionKey The session key associated with the message.
     */
    public string $sessionKey;

    /**
     * @var string $remotePhoneNumber The remote phone number involved in the message.
     */
    public string $remotePhoneNumber;

    /**
     * @var string $channelPhoneNumber The channel phone number associated with the message.
     */
    public string $channelPhoneNumber;

    /**
     * @var string $sentBy Indicates who sent the message (e.g., "user" or "contact").
     */
    public string $sentBy;

    /**
     * @var array $payload The payload data.
     */
    public array $payload;

    public function process(): void
    {
        $handlers = MessageHandlerFactory::create($this);
        if (!is_array($handlers)) {
            $handlers->handle();
            return;
        }

        // If multiple handlers are returned, process each one
        foreach ($handlers as $handler) {
            $handler->handle();
        }
    }
}
