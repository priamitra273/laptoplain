<?php

use App\Http\Controllers\Api\SprintReportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MsProjectPriorityController;
use App\Http\Controllers\MsProjectRoleController;
use App\Http\Controllers\MsProjectStatusController;
use App\Http\Controllers\MsTaskPriorityController;
use App\Http\Controllers\MsTaskStatusController;
use App\Http\Controllers\MsTaskTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectSummaryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskActivityController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskReportController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkLoadUserController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::get('/', fn () => to_route('login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('route.permission')->group(function () {
        $except = ['create', 'show', 'edit'];

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('workload-users', [WorkLoadUserController::class, 'index'])->name('workload-users.index');

        Route::resource('menu', MenuController::class)->except($except)->whereUuid('menu');
        Route::resource('user', UserController::class)->except('show');
        Route::resource('team', TeamController::class)->except($except)->whereUuid('team');
        Route::resource('role', RoleController::class);

        Route::resource('project-status', MsProjectStatusController::class)->except($except);
        Route::resource('project-priority', MsProjectPriorityController::class)->except($except);
        Route::resource('project-role', MsProjectRoleController::class)->except(['show']);

        Route::resource('task-priority', MsTaskPriorityController::class)->except($except);
        Route::resource('task-status', MsTaskStatusController::class)->except($except);
        Route::resource('task-type', MsTaskTypeController::class)->except($except);
        Route::resource('task-category', TaskCategoryController::class)->except($except);

        Route::resource('tag', TagController::class)->except($except);

        Route::resource('project', ProjectController::class)->except(['create', 'edit', 'show']);
        Route::get('project/{encoded}', [ProjectController::class, 'show'])->name('project.show');
        Route::get('project/{encoded}/summary', ProjectSummaryController::class)->name('project.summary');

        Route::get('task', [TaskController::class, 'index'])->name('task.index');

        Route::get('reports/tasks', [TaskReportController::class, 'index'])->name('reports.tasks.index');
        Route::get('reports/tasks/export', [TaskReportController::class, 'export'])->name('reports.tasks.export');
    });

    Route::prefix('task')->name('task.')->group(function () {
        Route::get('{task}', [TaskController::class, 'show'])->name('show');
        Route::get('{task}/comment', [TaskController::class, 'comments'])->name('comments');
        Route::get('{task}/parents', [TaskController::class, 'parents'])->name('parents');
        Route::put('{task}/parents', [TaskController::class, 'update_parents'])->name('parents.update');
        Route::put('{task}/status', [TaskController::class, 'updateStatus'])->name('status.update');
        Route::get('{encoded}/activities', [TaskActivityController::class, 'index'])->name('activities');
    });

    Route::prefix('project/{projectEncoded}')->name('project.')->group(function () {
        Route::post('members', [ProjectMemberController::class, 'store'])->name('members.store');
        Route::put('members/{memberEncoded}', [ProjectMemberController::class, 'update'])->name('members.update');
        Route::delete('members/{memberEncoded}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('tasks/{taskEncoded}', [TaskController::class, 'update'])->name('tasks.update');
        Route::put('tasks/{task}/priority', [TaskController::class, 'updatePriority'])->name('tasks.priority.update');
        Route::put('tasks/{task}/parent', [TaskController::class, 'updateParent'])->name('tasks.parent.update');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::prefix('sprints')->name('sprints.')->group(function () {
            Route::get('/', [SprintController::class, 'index'])->name('index');
            Route::post('/', [SprintController::class, 'store'])->name('store');
            Route::put('{sprintEncoded}', [SprintController::class, 'update'])->name('update');
            Route::delete('{sprintEncoded}', [SprintController::class, 'destroy'])->name('destroy');

            Route::patch('{sprintEncoded}/start', [SprintController::class, 'start'])->name('start');
            Route::patch('{sprintEncoded}/complete', [SprintController::class, 'complete'])->name('complete');

            Route::post('{sprintEncoded}/tasks', [SprintController::class, 'assignTask'])->name('tasks.assign');
            Route::delete('{sprintEncoded}/tasks/{taskEncoded}', [SprintController::class, 'removeTask'])->name('tasks.remove');
        });
    });

    Route::prefix('project/{project}/sprints')->name('sprints.')->group(function () {
        Route::get('all', [SprintReportController::class, 'index'])->name('all');
        Route::get('{projectSprint}/burndown', [SprintReportController::class, 'burndown'])->name('burndown');
        Route::get('{projectSprint}/status-report', [SprintReportController::class, 'statusReport'])->name('status-report');
    });

    Route::prefix('comments')->name('comments.')->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::put('{comment}', [CommentController::class, 'update'])->name('update');
        Route::delete('{comment}', [CommentController::class, 'destroy'])->name('destroy');
        Route::post('{comment}/reaction', [CommentController::class, 'react'])->name('react');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('stream', [NotificationController::class, 'stream'])->name('stream');
        Route::post('{encoded}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('clear', [NotificationController::class, 'clearAll'])->name('clear');
    });

    Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::fallback(function () {
    throw new NotFoundHttpException(404);
});
