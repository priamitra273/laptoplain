<?php

namespace App\Providers;

use App\Observers\MediaObserver;
use App\Services\SqidsService;
use App\Services\TaskNotificationService;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('sqids', function ($app) {
            return new SqidsService;
        });
        $this->app->singleton('task_notification', function ($app) {
            return new TaskNotificationService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Media::observe(MediaObserver::class);
    }
}
