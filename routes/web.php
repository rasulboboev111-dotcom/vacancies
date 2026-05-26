<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\PositionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/employees/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
    Route::post('/employees/{employee}/rotate', [EmployeeController::class, 'rotate'])->name('employees.rotate');
    Route::get('/rotations', [EmployeeController::class, 'rotationsIndex'])->name('rotations.index');
    
    Route::resource('employees', EmployeeController::class)->except(['show', 'create', 'edit']);
    Route::resource('branches', BranchController::class)->except(['show', 'create', 'edit']);
    Route::resource('positions', PositionController::class)->except(['show', 'create', 'edit']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // Trash / Recycle Bin
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/employees/{id}/restore', [TrashController::class, 'restoreEmployee'])->name('trash.employees.restore');
    Route::delete('/trash/employees/{id}/force', [TrashController::class, 'forceDeleteEmployee'])->name('trash.employees.force');
    Route::post('/trash/branches/{id}/restore', [TrashController::class, 'restoreBranch'])->name('trash.branches.restore');
    Route::delete('/trash/branches/{id}/force', [TrashController::class, 'forceDeleteBranch'])->name('trash.branches.force');
    Route::post('/trash/users/{id}/restore', [TrashController::class, 'restoreUser'])->name('trash.users.restore');
    Route::delete('/trash/users/{id}/force', [TrashController::class, 'forceDeleteUser'])->name('trash.users.force');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
