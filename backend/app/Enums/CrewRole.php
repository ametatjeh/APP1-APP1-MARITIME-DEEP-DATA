<?php

namespace App\Enums;

enum CrewRole: string
{
    case MASTER_SKIPPER = 'master_skipper';
    case CHIEF_ENGINEER = 'chief_engineer';
    case SECOND_ENGINEER = 'second_engineer';
    case BOSUN = 'bosun';
    case DECKHAND = 'deckhand';
    case COOK = 'cook';
    case FISHERIES_OBSERVER = 'fisheries_observer';
    case TRAINEE = 'trainee';
    case OTHER = 'other';
}
