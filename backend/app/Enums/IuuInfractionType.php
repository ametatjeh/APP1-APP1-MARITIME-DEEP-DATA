<?php

namespace App\Enums;

enum IuuInfractionType: string
{
    case UNLICENSED_FISHING = 'unlicensed_fishing';
    case UNAUTHORIZED_TRANSSHIPMENT = 'unauthorized_transshipment';
    case FISHING_IN_CLOSED_AREA = 'fishing_in_closed_area';
    case PROHIBITED_GEAR = 'prohibited_gear';
    case SHARK_FINNING = 'shark_finning';
    case AIS_SPOOFING_DARK_VESSEL = 'ais_spoofing_dark_vessel';
    case FLAG_HOPPING = 'flag_hopping';
    case TAMPERING_WITH_VMS = 'tampering_with_vms';
    case QUOTA_EXCEEDED = 'quota_exceeded';
    case HUMAN_RIGHTS_ABUSE = 'human_rights_abuse';
    case OTHER = 'other';
}
