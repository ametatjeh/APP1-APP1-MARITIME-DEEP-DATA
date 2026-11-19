<?php

namespace App\Enums;

enum IuuEventCategory: string
{
    case OFFICIAL_ADJUDICATION = 'official_adjudication';
    case OFFICIAL_ALLEGATION = 'official_allegation';
    case NGO_INVESTIGATION_FINDING = 'ngo_investigation_finding';
    case ANALYTICAL_ASSESSMENT = 'analytical_assessment';
    case UNVERIFIED_REPORT = 'unverified_report';
}
