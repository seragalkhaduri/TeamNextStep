<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule retry for failed notifications every 5 minutes (SDD §13)
Schedule::command('uimp:notifications:retry')->everyFiveMinutes();
