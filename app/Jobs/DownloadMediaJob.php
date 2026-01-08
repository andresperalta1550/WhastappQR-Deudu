<?php

namespace App\Jobs;

use App\ValueObjects\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $messageId,
        public string $remoteUrl
    ) {
    }

    /**
     * Execute the job.
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $message = (new \App\Models\Message)->findOrFail($this->messageId);

        if (!$message) {
            return;
        }

        // Download the media from the remote URL
        $response = Http::withOptions([
            'decode_content' => false,
        ])->get($this->remoteUrl);

        if (!$response->successful()) {
            return;
        }

        $binaryData = $response->body();

        // Create a unique filename
        $filename = "media/{$this->messageId}_" . basename($this->remoteUrl);

        Storage::disk('public')->put(
            $filename,
            $binaryData
        );

        // Obtain the size
        $sizeBytes = Storage::disk('public')->size($filename);

        // Update the message record with the local media path and size
        $message->setMedia(new Media(
            url: Storage::disk('public')->url($filename),
            type: $message->getMedia()->type,
            mimeType: $message->getMedia()->mimeType,
            sizeBytes: $sizeBytes
        ));
        $message->save();
    }
}
