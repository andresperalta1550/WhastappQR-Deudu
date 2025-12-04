<?php

namespace App\Libraries\Whatsapp\Webhook\Events;

use App\Models\Channel;
use App\Models\ChannelStatusEvent;
use App\ValueObjects\LastStatusEvent;
use Carbon\Carbon;

class StatusChannelEvent extends WebhookEvent
{
    /**
     * @var string $qrCode The QR code data
     */
    public string $qrCode;

    /**
     * @var array $payload The payload data
     */
    public array $payload;

    /**
     * Process the status channel event.
     *
     * @return void
     */
    public function process(): void
    {
        // We create the event record in the database
        (new ChannelStatusEvent())->create([
            'channel_uuid' => $this->channelUuid,
            'event' => $this->eventType,
            'qr_code' => $this->qrCode,
            'payload' => $this->payload
        ]);

        // Additional we need modified the channel last status
        // event in the model channel
        $channel = (new \App\Models\Channel)
            ->where('channel_uuid', $this->channelUuid)
            ->firstOrFail();

        $channel->setLastStatusEvent(
            new LastStatusEvent(
                $this->eventType,
                $this->qrCode,
                Carbon::parse($this->timestamp)
            )
        );

        $channel->save();
    }

    /**
     * Convert the status channel event to an array.
     *
     * @return array The status channel event as an array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['channel_uuid'] = $this->channelUuid;
        $data['event'] = $this->eventType;
        $data['qr_code'] = $this->qrCode;
        $data['payload'] = $this->payload;
        return $data;
    }
}
