<?php

namespace App\Libraries\Whatsapp\Webhook\Handlers;

use App\ValueObjects\Quoted;

class QuotedMessageHandler extends MessageHandler
{

    public function handle(): void
    {
        $model = $this->baseModel();

        $messageQuoted = $this->event->payload['message']['quoted_msg']['message'] ?? '';

        $model->setText($this->event->payload['message']['text'] ?? '');
        $model->setQuoted(new Quoted(
            message: $messageQuoted
        ));

        $model->save();
    }
}