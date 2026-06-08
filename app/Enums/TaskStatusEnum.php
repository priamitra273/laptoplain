<?php

namespace App\Enums;

enum TaskStatusEnum: string
{
    case TO_DO = 'To Do';
    case IN_PROGRESS = 'In Progress';
    case IN_REVIEW = 'In Review';
    case COMPLETED = 'Completed';
    case BLOCKED = 'Blocked';
    case FINISHED = 'Finished';

    public function label(): string
    {
        return $this->value;
    }
}
