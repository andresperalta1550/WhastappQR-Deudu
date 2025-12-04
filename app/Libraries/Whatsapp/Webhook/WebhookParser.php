<?php

namespace App\Libraries\Whatsapp\Webhook;

use App\Jobs\ResolveIncomingMessageContactJob;
use App\Libraries\Whatsapp\Webhook\Events\EditEvent;
use App\Libraries\Whatsapp\Webhook\Events\MessageEvent;
use App\Libraries\Whatsapp\Webhook\Events\ReactionEvent;
use App\Libraries\Whatsapp\Webhook\Events\StatusChannelEvent;
use App\Libraries\Whatsapp\Webhook\Events\StatusMessageEvent;
use App\Libraries\Whatsapp\Webhook\Events\WebhookEvent;
use Illuminate\Http\Request;

class WebhookParser
{
    public function parse(Request $request): ?WebhookEvent
    {
        $payload = $request->input();

        if (empty(($payload))) {
            return null;
        }

        $data = $payload;

        if (!empty($data['reaction'])) {
            return $this->parseReaction($data);
        }

        if (!empty($data['message']) && array_key_exists('edited_at', $data['message'])) {
            return $this->parseMessageEdit($data);
        }

        if (!empty($data['message'])) {
            $event = $this->parseMessage($data);
            ResolveIncomingMessageContactJob::dispatch($event);
            return $event;
        }

        if (!empty($data['event']) && str_contains($data['event'], 'message')) {
            return $this->parseStatusMessage($data);
        }

        return $this->parseStatusChannel($data);
    }

    private function parseStatusChannel(array $data): StatusChannelEvent
    {
        $event = new StatusChannelEvent();
        $event->eventType = $data['event'];
        $event->channelUuid = $data['channel_uuid'];
        $event->timestamp = $data['timestamp'];
        $event->qrCode = $data['payload']['qr'] ?? '';
        $event->payload = $data;

        return $event;
    }

    private function parseMessage(array $data): MessageEvent
    {
        $event = new MessageEvent();
        $event->eventType = array_key_last($data['message'] ?? []);
        $event->timestamp = $data['created_at'];
        $event->uuid = $data['uuid'];
        $event->sessionKey = $data['session_key'];
        $event->remotePhoneNumber = $data['remote_phone_number'];
        $event->channelPhoneNumber = $data['channel_phone_number'];
        $event->sentBy = $data['sent_by'];
        $event->payload = $data;

        return $event;
    }

    private function parseStatusMessage(array $data): StatusMessageEvent
    {
        $event = new StatusMessageEvent();
        $event->eventType = $data['event'];
        $event->uuid = $data['message_uuid'];
        $event->channelUuid = $data['channel_uuid'];
        $event->timestamp = $data['timestamp'];


        return $event;
    }

    private function parseReaction(array $data): ReactionEvent
    {
        $event = new ReactionEvent();
        $event->eventType = 'reaction';
        $event->message = $data['message'];
        $event->reaction = $data['reaction'];
        $event->timestamp = $data['message']['created_at'];
        $event->payload = $data;

        return $event;
    }

    private function parseMessageEdit(array $data): EditEvent
    {
        $event = new EditEvent();
        $event->eventType = 'edit';
        $event->uuid = $data['message']['uuid'];
        $event->message = $data['message']['message'];
        $event->timestamp = $data['message']['edited_at'];
        $event->payload = $data;

        return $event;
    }
}
