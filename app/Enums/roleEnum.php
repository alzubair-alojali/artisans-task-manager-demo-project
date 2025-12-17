<?php

namespace App\Enums;

enum roleEnum: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case USER = 'user';
}