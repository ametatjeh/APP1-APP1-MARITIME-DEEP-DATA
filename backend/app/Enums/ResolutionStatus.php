<?php

namespace App\Enums;

enum ResolutionStatus: string
{
    case UNREVIEWED = 'unreviewed';
    case UNDER_REVIEW = 'under_review';
    case CONFIRMED_DISTINCT_VESSELS = 'confirmed_distinct_vessels';
    case CONFIRMED_SAME_VESSEL_PENDING_MERGE = 'confirmed_same_vessel_pending_merge';
    case DISMISSED_NOISE = 'dismissed_noise';

    public function label(): string
    {
        return match($this) {
            self::UNREVIEWED => 'Unreviewed by Analyst',
            self::UNDER_REVIEW => 'Under Active Review',
            self::CONFIRMED_DISTINCT_VESSELS => 'Confirmed Distinct Vessels (Shared ID History)',
            self::CONFIRMED_SAME_VESSEL_PENDING_MERGE => 'Confirmed Same Vessel (Pending Merge Authorization)',
            self::DISMISSED_NOISE => 'Dismissed as Data Noise / Typo',
        };
    }
}
