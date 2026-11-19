<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case UNVERIFIED = 'unverified';
    case VERIFIED = 'verified';
    case DISPUTED = 'disputed';
    case FLAGGED_SUSPICIOUS = 'flagged_suspicious';

    public function label(): string
    {
        return match($this) {
            self::UNVERIFIED => 'Unverified',
            self::VERIFIED => 'Verified',
            self::DISPUTED => 'Disputed',
            self::FLAGGED_SUSPICIOUS => 'Flagged Suspicious',
        };
    }
}
