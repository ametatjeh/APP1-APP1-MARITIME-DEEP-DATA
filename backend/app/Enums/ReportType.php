<?php

namespace App\Enums;

enum ReportType: string
{
    case VESSEL_DOSSIER = 'vessel_dossier';
    case CORPORATE_NETWORK_INVESTIGATION = 'corporate_network_investigation';
    case CASE_SUMMARY = 'case_summary';
    case IUU_INFRACTION_BRIEF = 'iuu_infraction_brief';
    case FORCED_LABOUR_ASSESSMENT = 'forced_labour_assessment';
}
