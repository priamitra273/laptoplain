<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MsProjectPriorityController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MsProjectStatusController;
use App\Http\Controllers\MsTaskStatusController;
use App\Http\Controllers\MsTaskTypeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // return Inertia::render('Welcome');
    return to_route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('dashboard/statistic/{project_uuid}', [DashboardController::class, 'statistic'])->middleware(['auth', 'verified'])->name('dashboard.statistic');
Route::get('dashboard/map/{project_uuid}', [DashboardController::class, 'map'])->middleware(['auth', 'verified'])->name('dashboard.map');

Route::middleware(['auth', 'verified'])->group(function () {
    $except_route = ['create', 'show', 'edit'];



    Route::resource('menu', MenuController::class)->except($except_route)->whereUuid('menu');
    Route::resource('user', UserController::class)->except('show');
    Route::resource('team', TeamController::class)->except($except_route)->whereUuid('team');
    Route::resource('role', RoleController::class);

    Route::resource('project', ProjectController::class)->except($except_route)->whereUuid('project');

    Route::resource('ms_project_status', MsProjectStatusController::class)
    ->except(['create', 'show', 'edit']);


    Route::resource('ms_project_priority', MsProjectPriorityController::class)->except($except_route);

    Route::resource('ms_task_status', MsTaskStatusController::class)->except($except_route);

    Route::resource('ms_task_type', MsTaskTypeController::class)->except($except_route);
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
