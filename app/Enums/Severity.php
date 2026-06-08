<?php

namespace App\Enums;

enum Severity: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warn';
    case DANGER = 'danger';
}
