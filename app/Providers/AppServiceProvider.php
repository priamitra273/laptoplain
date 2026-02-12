<?php

namespace App\Providers;

use App\Services\SqidsService;
use App\Services\TaskNotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('sqids', function ($app) {
            return new SqidsService();
        });
        $this->app->singleton('task_notification', function ($app) {
            return new TaskNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
