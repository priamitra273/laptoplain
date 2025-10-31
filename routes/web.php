<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
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
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
