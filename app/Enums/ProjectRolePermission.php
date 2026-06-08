<?php

namespace App\Enums;

enum ProjectRolePermission: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
