<?php

namespace App\Enums;

enum SubjectRole: string
{
    case PRIMARY_TARGET = 'primary_target';
    case BENEFICIAL_OWNER = 'beneficial_owner';
    case OPERATOR = 'operator';
    case ASSOCIATED_VESSEL = 'associated_vessel';
    case FLAG_STATE = 'flag_state';
    case SUSPECTED_VESSEL = 'suspected_vessel';
    case CORPORATE_PARENT = 'corporate_parent';
    case TRANSSHIPMENT_PARTNER = 'transshipment_partner';
    case PORT_FACILITY = 'port_facility';
    case WITNESS = 'witness';
    case LOCATION = 'location';

    public function label(): string
    {
        return match($this) {
            self::PRIMARY_TARGET => 'Primary Investigation Target',
            self::BENEFICIAL_OWNER => 'Ultimate Beneficial Owner',
            self::OPERATOR => 'Vessel Operator / Manager',
            self::ASSOCIATED_VESSEL => 'Associated Fleet Vessel',
            self::FLAG_STATE => 'Flag State Jurisdiction',
            self::SUSPECTED_VESSEL => 'Suspected Perpetrator Vessel',
            self::CORPORATE_PARENT => 'Corporate Parent Holding',
            self::TRANSSHIPMENT_PARTNER => 'Transshipment Partner (Reefer / Bunker)',
            self::PORT_FACILITY => 'Port of Call / Landing Facility',
            self::WITNESS => 'Witness / Whistleblower Source',
            self::LOCATION => 'Geographic / Maritime Incident Location',
        };
    }
}
