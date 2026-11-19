<?php

namespace App\Enums;

enum CaseConfidentiality: string
{
    case STANDARD = 'standard';
    case RESTRICTED = 'restricted';
    case SECRET = 'secret';

    public function label(): string
    {
        return match($this) {
            self::STANDARD => 'Standard NGO Internal',
            self::RESTRICTED => 'Restricted Investigation Team',
            self::SECRET => 'Special Operations / Whistleblower Sensitive',
        };
    }
}
