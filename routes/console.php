<?php

use App\Jobs\ExpirePendingBookings;
use App\Jobs\MarkNoShowBookings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpirePendingBookings)->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new MarkNoShowBookings)->dailyAt('00:30')->withoutOverlapping();
