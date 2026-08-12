<?php

use App\Services\EventReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('events:send-reminders', function (EventReminderService $reminders) {
    $count = $reminders->sendDue();
    $this->info("Sent {$count} event reminder notification(s).");
})->purpose('Send due event reminders to registered students');

Schedule::command('events:send-reminders')->everyMinute()->withoutOverlapping();
