<?php

namespace App\Enums;

enum AnalyticStatusEnum: int
{
    case OPEN = 1;
    case DRAFT = 2;
    case CANCEL = 3;
    case COMPLETE = 4;
}
