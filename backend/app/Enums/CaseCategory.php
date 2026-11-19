<?php

namespace App\Enums;

enum CaseCategory: string
{
    case IUU_FISHING = 'iuu_fishing';
    case FORCED_LABOUR_HUMAN_TRAFFICKING = 'forced_labour_human_trafficking';
    case FLAG_HOPPING_IDENTITY_FRAUD = 'flag_hopping_identity_fraud';
    case SANCTIONS_EVASION = 'sanctions_evasion';
    case TAX_CUSTOMS_FRAUD = 'tax_customs_fraud';
    case TRANSSHIPMENT_ABUSE = 'transshipment_abuse';
    case ENVIRONMENTAL_CRIME = 'environmental_crime';

    public function label(): string
    {
        return match($this) {
            self::IUU_FISHING => 'IUU / Illegal Fishing Operations',
            self::FORCED_LABOUR_HUMAN_TRAFFICKING => 'Forced Labour & Human Trafficking at Sea',
            self::FLAG_HOPPING_IDENTITY_FRAUD => 'Flag Hopping & Vessel Identity Cloning',
            self::SANCTIONS_EVASION => 'Maritime Sanctions & Embargo Evasion',
            self::TAX_CUSTOMS_FRAUD => 'Fisheries Tax & Customs Tariff Evasion',
            self::TRANSSHIPMENT_ABUSE => 'Unauthorized High Seas Transshipment',
            self::ENVIRONMENTAL_CRIME => 'Marine Habitat Destruction & Prohibited Gear',
        };
    }
}
