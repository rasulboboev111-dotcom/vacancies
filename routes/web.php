<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacancyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// throttle:120,1 — a generous 120 requests/min per user caps scripted abuse
// (mass restore/force-delete, scraping) without affecting normal interactive use.
Route::middleware(['auth', 'verified', 'throttle:120,1'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/employees/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
    Route::post('/employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore')->whereNumber('id');
    Route::post('/employees/{id}/rotate', [EmployeeController::class, 'rotate'])->name('employees.rotate')->whereNumber('id');
    Route::get('/rotations', [EmployeeController::class, 'rotationsIndex'])->name('rotations.index');
    Route::delete('/rotations', [EmployeeController::class, 'clearRotations'])->name('rotations.clear');
    Route::get('/employees/managers', [EmployeeController::class, 'managers'])->name('employees.managers');

    Route::resource('employees', EmployeeController::class)->except(['show', 'create', 'edit'])->parameters(['employees' => 'id'])->whereNumber('id');
    Route::resource('branches', BranchController::class)->only(['store', 'update', 'destroy'])->parameters(['branches' => 'id'])->whereNumber('id');
    Route::resource('departments', DepartmentController::class)->only(['store', 'update', 'destroy'])->parameters(['departments' => 'id'])->whereNumber('id');
    Route::resource('vacancies', VacancyController::class)->except(['show', 'create', 'edit'])->parameters(['vacancies' => 'id'])->whereNumber('id');
    Route::get('/structure', [StructureController::class, 'index'])->name('structure.index');
    Route::get('/structure/branches/{id}/employees', [StructureController::class, 'branchEmployees'])->name('structure.branch.employees')->whereNumber('id');
    Route::get('/structure/departments/{id}/employees', [StructureController::class, 'departmentEmployees'])->name('structure.department.employees')->whereNumber('id');
    Route::resource('positions', PositionController::class)->except(['show', 'create', 'edit'])->parameters(['positions' => 'id'])->whereNumber('id');
    Route::resource('users', UserController::class)->except(['show', 'create', 'edit'])->parameters(['users' => 'id'])->whereNumber('id');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::delete('/activity-logs', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');

    // Trash / Recycle Bin
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/employees/{id}/restore', [TrashController::class, 'restoreEmployee'])->name('trash.employees.restore');
    Route::delete('/trash/employees/{id}/force', [TrashController::class, 'forceDeleteEmployee'])->name('trash.employees.force');
    Route::post('/trash/branches/{id}/restore', [TrashController::class, 'restoreBranch'])->name('trash.branches.restore');
    Route::delete('/trash/branches/{id}/force', [TrashController::class, 'forceDeleteBranch'])->name('trash.branches.force');
    Route::post('/trash/departments/{id}/restore', [TrashController::class, 'restoreDepartment'])->name('trash.departments.restore');
    Route::delete('/trash/departments/{id}/force', [TrashController::class, 'forceDeleteDepartment'])->name('trash.departments.force');
    Route::post('/trash/users/{id}/restore', [TrashController::class, 'restoreUser'])->name('trash.users.restore');
    Route::delete('/trash/users/{id}/force', [TrashController::class, 'forceDeleteUser'])->name('trash.users.force');
});

Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
