<?php

use App\Http\Controllers\Api\ApplicationIntakeController;
use Illuminate\Support\Facades\Route;

Route::post('/applications', [ApplicationIntakeController::class, 'store'])
    ->middleware(['api.key', 'throttle:120,1'])
    ->name('api.applications.store');
