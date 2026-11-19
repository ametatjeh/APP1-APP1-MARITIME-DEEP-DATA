<?php

namespace App\Enums;

enum CompanyAssignmentType: string
{
    case REGISTERED_OWNER = 'registered_owner';
    case BENEFICIAL_OWNER = 'beneficial_owner';
    case COMMERCIAL_MANAGER = 'commercial_manager';
    case TECHNICAL_MANAGER = 'technical_manager';
    case OPERATOR = 'operator';
    case CHARTERER = 'charterer';

    public function label(): string
    {
        return match($this) {
            self::REGISTERED_OWNER => 'Registered Owner',
            self::BENEFICIAL_OWNER => 'Beneficial Owner (UBO)',
            self::COMMERCIAL_MANAGER => 'Commercial Manager',
            self::TECHNICAL_MANAGER => 'Technical Manager',
            self::OPERATOR => 'Vessel Operator',
            self::CHARTERER => 'Charterer',
        };
    }
}
