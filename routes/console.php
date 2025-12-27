<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Observers\NotificationObserver;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule assignment due soon notifications (daily at 9 AM)
Schedule::call(function () {
    NotificationObserver::sendAssignmentDueSoonNotifications();
})->dailyAt('09:00');
