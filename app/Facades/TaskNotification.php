<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TaskNotification extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'task_notification';
    }
}
