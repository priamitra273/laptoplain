<?php

namespace App\Providers;

use App\Observers\MediaObserver;
use App\Services\SqidsService;
use App\Services\TaskNotificationService;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
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

        LogViewer::auth(function ($request) {
            return app()->isProduction()
                ? $request->user() && $request->user()->email === 'dhenistian.dickie@balitower.co.id'
                : true;
        });
    }
}
