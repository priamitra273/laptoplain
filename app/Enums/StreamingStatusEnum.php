<?php

namespace App\Enums;

enum StreamingStatusEnum: int
{
    case OPEN = 1;
    case PROCESS = 2;
    case CANCEL = 3;
    case COMPLETE = 4;
}
