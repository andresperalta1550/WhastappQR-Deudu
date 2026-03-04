<?php

namespace App\Jobs;

use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

/**
 * Hourly job that finds messages missing a debtor_id and backfills it
 * by joining against the contacts collection using the same
 * remote_phone_number + channel_phone_number pair.
 *
 * Only messages whose matching contact already has a non-null debtor_id
 * will be updated.
 */
class BackfillMessageDebtorIdJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Keep the job unique for 1 hour so it does not run in parallel
     * with itself even if the scheduler fires while a previous run is
     * still in progress.
     *
     * @var int
     */
    public int $uniqueFor = 3600;

    /**
     * Unique identifier for the job lock.
     */
    public function uniqueId(): string
    {
        return 'backfill-message-debtor-id';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[BackfillMessageDebtorIdJob] Starting debtor_id backfill run.');

        $pipeline = [
            // 1️⃣ Only messages without debtor_id
            [
                '$match' => [
                    '$or' => [
                        ['debtor_id' => ['$exists' => false]],
                        ['debtor_id' => null],
                    ],
                ],
            ],

            // 2️⃣ Join against contacts on remote + channel phone numbers,
            //    requiring the contact to already have a debtor_id
            [
                '$lookup' => [
                    'from'     => 'contacts',
                    'let'      => [
                        'remote'  => '$remote_phone_number',
                        'channel' => '$channel_phone_number',
                    ],
                    'pipeline' => [
                        [
                            '$match' => [
                                '$expr' => [
                                    '$and' => [
                                        ['$eq' => ['$remote_phone_number',  '$$remote']],
                                        ['$eq' => ['$channel_phone_number', '$$channel']],
                                        ['$ne' => ['$debtor_id', null]],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'as' => 'contact_match',
                ],
            ],

            // 3️⃣ Keep only messages that found at least one matching contact
            [
                '$match' => [
                    'contact_match' => ['$ne' => []],
                ],
            ],

            // 4️⃣ Promote the debtor_id from the first matching contact
            [
                '$addFields' => [
                    'suggested_debtor_id' => [
                        '$arrayElemAt' => ['$contact_match.debtor_id', 0],
                    ],
                ],
            ],
        ];

        $cursor = Message::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $updated = 0;
        $skipped = 0;

        foreach ($cursor as $doc) {
            $id          = $doc['_id']          ?? null;
            $debtorId    = $doc['suggested_debtor_id'] ?? null;

            if ($id === null || $debtorId === null) {
                $skipped++;
                continue;
            }

            try {
                Message::raw(function ($collection) use ($id, $debtorId) {
                    $collection->updateOne(
                        ['_id' => new ObjectId($id)],
                        ['$set' => ['debtor_id' => (int) $debtorId]]
                    );
                });

                Log::debug('[BackfillMessageDebtorIdJob] Message updated', [
                    'message_id' => (string) $id,
                    'debtor_id'  => $debtorId,
                ]);

                $updated++;
            } catch (\Throwable $e) {
                Log::error('[BackfillMessageDebtorIdJob] Failed to update message', [
                    'message_id' => (string) $id,
                    'error'      => $e->getMessage(),
                ]);
                $skipped++;
            }
        }

        Log::info('[BackfillMessageDebtorIdJob] Backfill run completed.', [
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }
}
