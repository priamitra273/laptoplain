<?php

use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ProjectController;

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

    Route::post('project/verify-import', [ProjectController::class, 'verify_import'])->name('project.verify-import');
    Route::post('project/import', [ProjectController::class, 'import'])->name('project.import');
    Route::get('project/export', [ProjectController::class, 'export'])->name('project.export');
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
