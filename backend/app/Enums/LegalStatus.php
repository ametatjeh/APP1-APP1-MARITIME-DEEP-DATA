<?php

namespace App\Enums;

enum LegalStatus: string
{
    case AGREED = 'agreed';
    case CLAIMED = 'claimed';
    case DISPUTED = 'disputed';
    case UNILATERAL = 'unilateral';
    case PROVISIONAL = 'provisional';

    public function label(): string
    {
        return match($this) {
            self::AGREED => 'Agreed / Bilateral Treaty',
            self::CLAIMED => 'Official National Claim',
            self::DISPUTED => 'Contested / Disputed Area',
            self::UNILATERAL => 'Unilateral Declaration',
            self::PROVISIONAL => 'Provisional Arrangement',
        };
    }
}
