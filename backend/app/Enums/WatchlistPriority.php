<?php

namespace App\Enums;

enum WatchlistPriority: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
}
