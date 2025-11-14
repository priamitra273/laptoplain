<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\RoleController;

use App\Http\Controllers\ProjectController;

use App\Http\Controllers\MsProjectStatusController;
use App\Http\Controllers\MsProjectPriorityController;
use App\Http\Controllers\MsProjectRoleController;

use App\Http\Controllers\MsTaskPriorityController;
use App\Http\Controllers\MsTaskStatusController;
use App\Http\Controllers\MsTaskTypeController;
use App\Http\Controllers\TagController;

Route::get('/', fn() => to_route('login'))->name('home');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/statistic/{project_uuid}', [DashboardController::class, 'statistic'])->name('dashboard.statistic');
    Route::get('dashboard/map/{project_uuid}', [DashboardController::class, 'map'])->name('dashboard.map');

    $except = ['create', 'show', 'edit'];

    Route::resource('menu', MenuController::class)->except($except)->whereUuid('menu');
    Route::resource('user', UserController::class)->except('show');
    Route::resource('team', TeamController::class)->except($except)->whereUuid('team');
    Route::resource('role', RoleController::class);

    Route::resource('project-status', MsProjectStatusController::class)->except($except);
    Route::resource('project-priority', MsProjectPriorityController::class)->except($except);
    Route::resource('project-role', MsProjectRoleController::class)->except($except);

    Route::resource('task-priority', MsTaskPriorityController::class)->except($except);
    Route::resource('task-status', MsTaskStatusController::class)->except($except);
    Route::resource('task-type', MsTaskTypeController::class)->except($except);

    Route::resource('tag', TagController::class)->except($except);

    Route::resource('project', ProjectController::class)
        ->except(['create', 'edit', 'show']);

    Route::get('project/{encoded}', [ProjectController::class, 'show'])
        ->name('project.show');

    Route::prefix('project/{encoded}')
        ->name('project.')
        ->group(function () {
            Route::get('members', [ProjectMemberController::class, 'members'])->name('members.members');
            Route::post('members', [ProjectMemberController::class, 'store'])->name('members.store');
            Route::put('members/{memberEncoded}', [ProjectMemberController::class, 'update'])->name('members.update');
            Route::delete('members/{memberEncoded}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');
        });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
