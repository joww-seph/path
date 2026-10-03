<?php

use App\Jobs\ExpirePendingBookings;
use App\Jobs\MarkNoShowBookings;
use App\Jobs\SendBookingReminders;
use App\Jobs\SendTripReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpirePendingBookings)->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new MarkNoShowBookings)->dailyAt('00:30')->withoutOverlapping();
Schedule::job(new SendTripReminders)->dailyAt('18:00')->withoutOverlapping();
Schedule::job(new SendBookingReminders)->everyFiveMinutes()->withoutOverlapping();
