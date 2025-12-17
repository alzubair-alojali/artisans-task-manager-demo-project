<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case OPEN = 'open';
    case COMPLETED = 'completed';
    case ARCHIVED = 'archived';
}
