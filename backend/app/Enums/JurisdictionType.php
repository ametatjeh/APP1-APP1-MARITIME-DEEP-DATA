<?php

namespace App\Enums;

enum JurisdictionType: string
{
    case INTERNAL_WATERS = 'internal_waters';
    case ARCHIPELAGIC_WATERS = 'archipelagic_waters';
    case TERRITORIAL_SEA = 'territorial_sea';
    case CONTIGUOUS_ZONE = 'contiguous_zone';
    case EEZ = 'eez';
    case CONTINENTAL_SHELF = 'continental_shelf';
    case EXTENDED_CONTINENTAL_SHELF = 'extended_continental_shelf';
    case DISPUTED_AREA = 'disputed_area';
    case JOINT_DEVELOPMENT_ZONE = 'joint_development_zone';

    public function label(): string
    {
        return match($this) {
            self::INTERNAL_WATERS => 'Internal Waters',
            self::ARCHIPELAGIC_WATERS => 'Archipelagic Waters',
            self::TERRITORIAL_SEA => 'Territorial Sea (12 NM)',
            self::CONTIGUOUS_ZONE => 'Contiguous Zone (24 NM)',
            self::EEZ => 'Exclusive Economic Zone (EEZ / ZEE)',
            self::CONTINENTAL_SHELF => 'Continental Shelf',
            self::EXTENDED_CONTINENTAL_SHELF => 'Extended Continental Shelf',
            self::DISPUTED_AREA => 'Disputed Maritime Area',
            self::JOINT_DEVELOPMENT_ZONE => 'Joint Development Zone',
        };
    }
}
