<?php

namespace App\Enums;

enum SiteStatus: int
{
    case OPEN = 1;
    case PROGRESS = 2;
    case RELOCATION = 3;
    case DISMANTLE = 4;
    case COMPLETE = 5;
}
