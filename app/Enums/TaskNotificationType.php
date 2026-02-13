<?php

namespace App\Enums;

enum TaskNotificationType: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';

    public function message(): string
    {
        return match ($this) {
            self::CREATED => 'created',
            self::UPDATED => 'updated',
            self::DELETED => 'deleted',
        };
    }
}
