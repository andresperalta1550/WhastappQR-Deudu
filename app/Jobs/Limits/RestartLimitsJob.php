<?php

namespace App\Jobs\Limits;

use App\Models\Channel;
use App\Models\LimitsValidatorBatch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RestartLimitsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Restart the limits of by administration validator batches.
     *
     * @return void
     */
    public function handle(): void
    {
        $limit = LimitsValidatorBatch::where('type', 'by_administration')->first();
        if (!$limit) {
            return;
        }

        $period = $limit->getPeriod();

        $cutoff = match ($period) {
            'daily' => now()->subDay(),
            'monthly' => now()->subMonth(),
            default => null,
        };

        if (!$cutoff) {
            return;
        }

        Channel::query()
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_limit_reset')
                    ->orWhere('last_limit_reset', '<', $cutoff);
            })
            ->update([
                'validator_usage' => 0,
                'last_limit_reset' => now(),
            ]);
    }

}
