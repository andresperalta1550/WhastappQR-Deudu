<?php

namespace App\Libraries\Whatsapp\Webhook;

use App\Libraries\Whatsapp\Webhook\Events\MessageEvent;
use App\Libraries\Whatsapp\Webhook\Handlers\MediaHandler;
use App\Libraries\Whatsapp\Webhook\Handlers\MessageHandler;
use App\Libraries\Whatsapp\Webhook\Handlers\QuotedMessageHandler;
use App\Libraries\Whatsapp\Webhook\Handlers\TextHandler;

class MessageHandlerFactory
{
    /**
     * @param MessageEvent $event
     * @return MessageHandler | MessageHandler[]
     */
    public static function create(
        MessageEvent $event
    ): MessageHandler|array {
        $message = $event->payload['message'] ?? [];

        if (count($message) === 1) {
            $type = array_key_first($message);

            return match ($type) {
                'text' => new TextHandler($event),
                'media' => new MediaHandler($event),
                'quoted_msg' => new QuotedMessageHandler($event),
                default => throw new \InvalidArgumentException("Unsupported message type: $type"),
            };
        }

        // If we have multiple message types, we can handle them accordingly
        $handlers = [];
        foreach ($message as $type => $payload) {
            $event->eventType = $type;
            $handlers[] = match ($type) {
                'text' => new TextHandler($event),
                'media' => new MediaHandler($event),
                'quoted_msg' => new QuotedMessageHandler($event),
                default => throw new \InvalidArgumentException("Unsupported message type: $type"),
            };
        }

        return $handlers;
    }


}
