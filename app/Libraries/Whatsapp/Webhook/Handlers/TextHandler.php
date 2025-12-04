<?php

namespace App\Libraries\Whatsapp\Webhook\Handlers;

class TextHandler extends MessageHandler
{
    /**
     * Process the text message event.
     *
     * @return void
     */
    public function handle(): void
    {
        $model = $this->baseModel();

        $model->setType('text');
        $model->setText($this->event->payload['message']['text'] ?? '');

        $model->save();
    }
}
