<?php

namespace App\Enums;

enum TaskField: string
{
    case TITLE = 'title';
    case DESCRIPTION = 'description';
    case STATUS = 'status';
    case PRIORITY = 'priority';
    case TYPE = 'type';
    case CATEGORY = 'category';
    case START_DATE = 'start_date';
    case END_DATE = 'end_date';
    case DUE_DATE = 'due_date';
    case TAGS = 'tags';
    case ASSIGNEE = 'assignee';
    case PARENT = 'parent';
}
