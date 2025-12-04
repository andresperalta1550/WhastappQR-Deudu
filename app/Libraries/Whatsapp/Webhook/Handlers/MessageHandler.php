<?php

namespace App\Libraries\Whatsapp\Webhook\Handlers;

use App\Libraries\Whatsapp\Webhook\Events\MessageEvent;
use App\Models\Message;
use App\ValueObjects\Delivery;

/**
 * That is the base class for all message handlers.
 */
abstract class MessageHandler
{
    /**
     * @var MessageEvent $event The message event instance.
     */
    protected MessageEvent $event;

    public function __construct(MessageEvent $event)
    {
        $this->event = $event;
    }

    /**
     * Handle the message event.
     *
     * @return void
     */
    abstract public function handle(): void;

    protected function baseModel(): Message
    {
        // We verify if the message already exists in the database
        // to avoid duplicates.
        $model = (new Message())
            ->where('message_uuid', $this->event->uuid)
            ->first();

        if ($model !== null) {
            return $model;
        }

        $model = new Message();

        $model->setMessageUuid($this->event->uuid);
        $model->setSessionKey($this->event->sessionKey);

        // The channel UUID is obtained when the message is already in the
        // API2Chat's database, so we don't need to set it here.
        $model->setChannelPhoneNumber($this->event->channelPhoneNumber);
        $model->setRemotePhoneNumber($this->event->remotePhoneNumber);
        // Assign the debtor_id if applicable

        $model->setDirection($this->event->sentBy === 'user' ? 'inbound' : 'outbound');
        $model->setSentBy($this->event->sentBy);
        $model->setSource(null); // Apply null because is a message received from webhook

        $model->setSentUserBy(null); // Apply null because is a message received from webhook

        $model->setStatus('delivered');

        $model->setDelivery(new Delivery(
            sentAt: isset($this->event->timestamp)
                ? \Carbon\Carbon::parse($this->event->timestamp) : null,
            deliveredAt: isset($this->event->timestamp)
                ? \Carbon\Carbon::parse($this->event->timestamp) : null,
            readAt: null,
        ));

        $model->setInternalRead(false);
        $model->setInternalReadAt(null); // Apply null because is a message received from webhook

        $model->setRawPayload($this->event->payload);
        return $model;
    }
}
