<?php

namespace App\Enums;

enum EvidenceClassification: string
{
    case UNCLASSIFIED = 'unclassified';
    case CONFIDENTIAL = 'confidential';
    case SECRET = 'secret';
    case STRICTLY_RESTRICTED = 'strictly_restricted';

    public function label(): string
    {
        return match($this) {
            self::UNCLASSIFIED => 'Unclassified Internal',
            self::CONFIDENTIAL => 'Confidential Intelligence',
            self::SECRET => 'Secret Investigation Material',
            self::STRICTLY_RESTRICTED => 'Strictly Restricted / Court Admissible',
        };
    }
}
