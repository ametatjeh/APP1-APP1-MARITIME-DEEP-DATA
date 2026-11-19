<?php

namespace App\Enums;

enum ConflictNature: string
{
    case SHARED_MMSI = 'shared_mmsi';
    case CLONED_IMO = 'cloned_imo';
    case RECYCLED_CALLSIGN = 'recycled_callsign';
    case DUAL_FLAG_REGISTRATION = 'dual_flag_registration';
    case SUSPICIOUS_ALIAS = 'suspicious_alias';

    public function label(): string
    {
        return match($this) {
            self::SHARED_MMSI => 'Shared / Reassigned MMSI',
            self::CLONED_IMO => 'Cloned / Spoofed IMO Number',
            self::RECYCLED_CALLSIGN => 'Recycled Radio Callsign',
            self::DUAL_FLAG_REGISTRATION => 'Dual Flag / Overlapping Registration',
            self::SUSPICIOUS_ALIAS => 'Suspicious Alternate Identity / Alias',
        };
    }
}
