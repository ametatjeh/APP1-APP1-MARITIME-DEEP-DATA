<?php

namespace App\Enums;

enum AlertStatus: string
{
    case ACTIVE = 'active';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
