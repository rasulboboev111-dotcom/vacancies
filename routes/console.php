<?php

use App\Jobs\SyncOrgStructure;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ежедневная синхронизация сотрудников/оргструктуры: тянем живую структуру
// Tojiktelecom и сверяем с ней наши branches/departments/employees
// (идемпотентный updateOrCreate). Работа выполняется на queue-воркере;
// SyncOrgStructure уникален, поэтому пересекающиеся запуски не накладываются.
Schedule::job(new SyncOrgStructure(api: true))->dailyAt('03:00');
