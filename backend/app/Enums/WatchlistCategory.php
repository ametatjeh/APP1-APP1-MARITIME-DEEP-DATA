<?php

namespace App\Enums;

enum WatchlistCategory: string
{
    case IUU_HIGH_RISK = 'iuu_high_risk';
    case FORCED_LABOUR_RISK = 'forced_labour_risk';
    case SANCTIONS_MONITORING = 'sanctions_monitoring';
    case IDENTITY_FRAUD = 'identity_fraud';
    case FLAGS_OF_CONVENIENCE = 'flags_of_convenience';
    case CUSTOM = 'custom';
}
