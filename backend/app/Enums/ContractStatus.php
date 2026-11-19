<?php

namespace App\Enums;

enum ContractStatus: string
{
    case COMPLETED = 'completed';
    case ACTIVE_ON_BOARD = 'active_on_board';
    case ABANDONED = 'abandoned';
    case REPATRIATED = 'repatriated';
    case DECEASED = 'deceased';
    case ESCAPED_REPORTED = 'escaped_reported';
    case DISPUTED = 'disputed';
}
