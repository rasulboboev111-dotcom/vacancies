<?php

use App\Jobs\SyncOrgStructure;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily employee/org sync: pull the live Tojiktelecom structure and reconcile
// our branches/departments/employees with it (idempotent updateOrCreate). The
// work runs on the queue worker; SyncOrgStructure is unique, so an overlapping
// run cannot stack.
Schedule::job(new SyncOrgStructure(api: true))->dailyAt('03:00');
