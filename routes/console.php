<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily employee/org sync: pull the live Tojiktelecom structure and reconcile
// our branches/departments/employees with it (idempotent updateOrCreate).
Schedule::command('org:import --api')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();
