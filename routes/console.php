<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::job(new \App\Jobs\SyncWhatsappChannelsJob())
    ->everyFiveMinutes()
    ->name('sync-whatsapp-channels')
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\Limits\RestartLimitsJob())
    ->dailyAt('00:00')
    ->name('restart-limits');