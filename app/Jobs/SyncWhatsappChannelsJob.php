<?php

namespace App\Jobs;

use App\Actions\StoreChannelAction;
use App\Libraries\Whatsapp\Client;
use App\Models\Channel;
use Exception;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWhatsappChannelsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $uniqueFor The number of seconds the job should be unique for.
     */
    public int $uniqueFor = 300; // 5 minutes

    /**
     * The unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'sync-whatsapp-channels';
    }

    /**
     * Execute the job.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        try {
            $client = new Client();
            $page = 0;
            $allNumbers = [];

            // Obtain all WhatsApp numbers with pagination
            do {
                $response = $client->getNumbersWithPagination($page);

                if (!$response['success'] || empty($response['numbers'])) {
                    break;
                }

                $allNumbers = array_merge($allNumbers, $response['numbers']);
                $page++;

                // If we received no numbers or reached a reasonable page limit, stop fetching
                if (count($response['numbers']) === 0 || $page >= 100) {
                    break;
                }
            } while (true);

            if (empty($allNumbers)) {
                Log::info('No se encontraron números de WhatsApp para sincronizar.');
                return;
            }

            // Extract all uuids of the fetched numbers
            $numberUuids = array_filter(array_column($allNumbers, 'uuid'));

            // Obtain all existing channel_uuids from the database
            $existingUuids = Channel::whereIn('channel_uuid', $numberUuids)
                ->pluck('channel_uuid')
                ->toArray();

            // Create a set for faster lookup
            $existingUuidsSet = array_flip($existingUuids);

            // UUIDs that exist in the database but are not in the synchronization
            $missingUuids = array_diff($existingUuids, $numberUuids);

            if (!empty($missingUuids)) {
                Channel::whereIn('channel_uuid', $missingUuids)
                    ->update([
                        'connection_status' => 'E',
                        'enabled' => false,
                    ]);

                Log::info('Channels disabled because they do not exist in synchronization', [
                    'count' => count($missingUuids),
                ]);
            }

            // Process each number
            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($allNumbers as $number) {
                $uuid = $number['uuid'] ?? null;

                if (!$uuid) {
                    $skipped++;
                    continue; // Skip if no UUID
                }

                // Verify if the channel already exists (using the set for O(1) lookup)
                $exists = isset($existingUuidsSet[$uuid]);

                try {
                    $result = (new StoreChannelAction())->handle($number, $exists);

                    if ($result === 'created') {
                        $created++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (Exception $e) {
                    Log::error("Error al guardar canal: " . $e->getMessage(), [
                        'uuid' => $uuid,
                        'phone_number' => $number['phone_number'] ?? null
                    ]);
                    $skipped++;
                }
            }

            Log::info('Sincronización de canales completada', [
                'total' => count($allNumbers),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'pages' => $page
            ]);

        } catch (Exception $e) {
            Log::error('Error en SyncWhatsappChannelsJob: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw the exception for further handling if needed
        }
    }
}
