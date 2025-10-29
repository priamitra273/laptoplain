<?php

use App\Http\Controllers\AnalyticController;
use App\Http\Controllers\AnalyticServerController;
use App\Http\Controllers\CctvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\HardwareController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PreconfigController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StreamController;
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

    Route::post('project/verify-import', [ProjectController::class, 'verify_import'])->name('project.verify-import');
    Route::post('project/import', [ProjectController::class, 'import'])->name('project.import');
    Route::get('project/export', [ProjectController::class, 'export'])->name('project.export');

    Route::get('site/datatable', [SiteController::class, 'datatable'])->name('site.datatable');
    Route::post('site/verify-import', [SiteController::class, 'verify_import'])->name('site.verify-import');
    Route::post('site/import', [SiteController::class, 'import'])->name('site.import');
    Route::get('site/export', [SiteController::class, 'export'])->name('site.export');

    Route::post('analytic-server/verify-import', [AnalyticServerController::class, 'verify_import'])->name('server.verify-import');
    Route::post('analytic-server/import', [AnalyticServerController::class, 'import'])->name('server.import');
    Route::get('analytic-server/export', [AnalyticServerController::class, 'export'])->name('server.export');
    Route::put('analytic-server/maintenance/{analytic_server}', [AnalyticServerController::class, 'maintenance'])->whereUuid('analytic_server')->name('server.maintenance');

    Route::post('hardware/verify-import', [HardwareController::class, 'verify_import'])->name('hardware.verify-import');
    Route::post('hardware/import', [HardwareController::class, 'import'])->name('hardware.import');
    Route::get('hardware/export', [HardwareController::class, 'export'])->name('hardware.export');

    Route::resource('site', SiteController::class)->whereUuid('site');
    Route::resource('department', DepartmentController::class)->except($except_route)->whereUuid('department');
    Route::resource('cctv', CctvController::class)->whereUuid('cctv');

    Route::resource('menu', MenuController::class)->except($except_route)->whereUuid('menu');
    Route::resource('user', UserController::class)->except('show');
    Route::resource('team', TeamController::class)->except($except_route)->whereUuid('team');
    Route::resource('role', RoleController::class);
    Route::resource('project', ProjectController::class)->except($except_route)->whereUuid('project');
    Route::resource('analytic-server', AnalyticServerController::class)->whereUuid('analytic_server');
    Route::resource('hardware', HardwareController::class)->whereUuid('hardware');
});

Route::prefix('apps')->middleware(['auth', 'verified'])->group(function () {
    Route::get('stream/datatable', [StreamController::class, 'datatable'])->name('stream.datatable');
    Route::post('stream/health-check', [StreamController::class, 'health'])->name('stream.health');
    Route::resource('stream', StreamController::class)->whereUuid('stream');

    Route::post('stream/verify-import', [StreamController::class, 'verify_import'])->name('stream.verify-import');
    Route::post('stream/import', [StreamController::class, 'import'])->name('stream.import');
    Route::get('stream/export', [StreamController::class, 'export'])->name('stream.export');

    Route::get('analytic/datatable', [AnalyticController::class, 'datatable'])->name('analytic.datatable');
    Route::resource('analytic', AnalyticController::class)->whereUuid('analytic');

    Route::post('analytic/verify-import', [AnalyticController::class, 'verify_import'])->name('analytic.verify-import');
    Route::post('analytic/import', [AnalyticController::class, 'import'])->name('analytic.import');
    Route::get('analytic/export', [AnalyticController::class, 'export'])->name('analytic.export');

    Route::get('analytic/{analytic}/thumbnail', [AnalyticController::class, 'thumbnail'])->name('analytic.thumbnail')->whereUuid('analytic');
});

Route::prefix('apps')->middleware(['auth', 'verified'])->group(function () {
    Route::get('preconfig/datatable', [PreconfigController::class, 'datatable'])->name('preconfig.datatable');
    Route::post('preconfig/verify-import', [PreconfigController::class, 'verify_import'])->name('preconfig.verify-import');
    Route::post('preconfig/import', [PreconfigController::class, 'import'])->name('preconfig.import');
    Route::get('preconfig/export', [PreconfigController::class, 'export'])->name('preconfig.export');

    Route::get('installation/datatable', [InstallationController::class, 'datatable'])->name('installation.datatable');
    Route::get('installation/{site}/log', [InstallationController::class, 'log'])->whereUuid('site')->name('installation.log');

    Route::post('device/verify-import', [DeviceController::class, 'verify_import'])->name('device.verify-import');
    Route::post('device/import', [DeviceController::class, 'import'])->name('device.import');
    Route::get('device/export', [DeviceController::class, 'export'])->name('device.export');

    Route::resource('device', DeviceController::class)->whereUuid('device');
    Route::resource('preconfig', PreconfigController::class)->whereUuid('preconfig');
    Route::resource('installation', InstallationController::class)->whereUuid('installation');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
