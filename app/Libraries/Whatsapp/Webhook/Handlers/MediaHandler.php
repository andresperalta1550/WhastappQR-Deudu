<?php

namespace App\Libraries\Whatsapp\Webhook\Handlers;

use App\ValueObjects\Media;

class MediaHandler extends MessageHandler
{

    /**
     * Process the media message event.
     *
     * @return void
     */
    public function handle(): void
    {
        $model = $this->baseModel();

        $message = $this->event->payload['message'] ?? [];

        $model->setType($message['media']['type'] ?? 'media');
        $model->setMedia(new Media(
            url: $message['media']['url'] ?? null,
            type: $message['media']['type'] ?? null,
            mimeType: $message['media']['mime_type'] ?? null,
            sizeBytes: null // Size in bytes is not provided in the webhook payload
        ));

        $model->save();

        // Launch the Job to download and store the media
        if (!empty($message['media']['url'])) {
            // Dispatch the job to download and store the media
            \App\Jobs\DownloadMediaJob::dispatch(
                $model->getId(),
                $message['media']['url']
            );
        }
    }
}
