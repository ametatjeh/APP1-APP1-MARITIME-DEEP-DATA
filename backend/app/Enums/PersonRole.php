<?php

namespace App\Enums;

enum PersonRole: string
{
    case OFFICER = 'officer';
    case DIRECTOR = 'director';
    case BENEFICIAL_OWNER = 'beneficial_owner';
    case SKIPPER = 'skipper';
    case CREW = 'crew';
    case MANNING_AGENT = 'manning_agent';
    case BROKER = 'broker';
    case TARGET = 'target';
    case OTHER = 'other';
}
