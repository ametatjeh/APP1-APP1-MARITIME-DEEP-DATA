<?php

namespace App\Enums;

enum ResearchPolygonCategory: string
{
    case HIGH_RISK_IUU_ZONE = 'high_risk_iuu_zone';
    case TRANSSHIPMENT_CORRIDOR = 'transshipment_corridor';
    case PATROL_AREA = 'patrol_area';
    case MARINE_PROTECTED_AREA = 'marine_protected_area';
    case INVESTIGATION_AOI = 'investigation_aoi';
    case DISPUTED_CLAIM_AOI = 'disputed_claim_aoi';
}
