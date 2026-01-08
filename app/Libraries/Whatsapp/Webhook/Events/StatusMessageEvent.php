<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use App\ValueObjects\Delivery;

class StatusMessageEvent extends WebhookEvent
{
    /**
     * @var string $uuid The UUID of the status message.
     */
    public string $uuid;

    /**
     * Process the status message event.
     *
     * @return void
     */
    public function process(): void
    {
        $message = (new \App\Models\Message())
            ->where('message_uuid', $this->uuid)
            ->firstOrFail([
                'id',
                'message_uuid',
                'status',
                'delivery',
            ]);

        $event = str_replace('message.', '', $this->eventType);

        /** @var \App\Models\Message $message */
        match ($event) {
            'sent' => $this->handleSentEvent($message, $event),
            'received' => $this->handleDeliveredEvent($message, $event),
            'read' => $this->handleReadEvent($message, $event),
            default => null,
        };

        $message->save();
    }

    private function handleSentEvent(\App\Models\Message $message, string $event): void
    {
        $message->setStatus($event);
        $message->setDelivery(new Delivery(
            sentAt: isset($this->timestamp) ? \Carbon\Carbon::parse($this->timestamp, 'UTC') : null,
            deliveredAt: $message->getDelivery()?->deliveredAt,
            readAt: $message->getDelivery()?->readAt
        ));
    }

    private function handleDeliveredEvent(\App\Models\Message $message, string $event): void
    {
        $message->setStatus('delivered');
        $message->setDelivery(new Delivery(
            sentAt: $message->getDelivery()?->sentAt,
            deliveredAt: isset($this->timestamp) ? \Carbon\Carbon::parse($this->timestamp, 'UTC') : null,
            readAt: $message->getDelivery()?->readAt
        ));
    }

    private function handleReadEvent(\App\Models\Message $message, string $event): void
    {
        $message->setStatus($event);
        $message->setDelivery(new Delivery(
            sentAt: $message->getDelivery()?->sentAt,
            deliveredAt: $message->getDelivery()?->deliveredAt,
            readAt: isset($this->timestamp) ? \Carbon\Carbon::parse($this->timestamp, 'UTC') : null
        ));
    }
}
