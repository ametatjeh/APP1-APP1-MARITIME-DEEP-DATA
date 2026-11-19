<?php

namespace App\Enums;

enum EvidenceType: string
{
    case DOCUMENT = 'document';
    case SATELLITE_IMAGERY = 'satellite_imagery';
    case VESSEL_PHOTOGRAPH = 'vessel_photograph';
    case REGISTRY_EXTRACT = 'registry_extract';
    case COURT_FILING = 'court_filing';
    case INTERVIEW_TRANSCRIPT = 'interview_transcript';
    case FINANCIAL_RECORD = 'financial_record';
    case CUSTOMS_MANIFEST = 'customs_manifest';
    case FIELD_REPORT = 'field_report';

    public function label(): string
    {
        return match($this) {
            self::DOCUMENT => 'Official Document / Certificate',
            self::SATELLITE_IMAGERY => 'Satellite Imagery Analysis',
            self::VESSEL_PHOTOGRAPH => 'Vessel Field Photograph',
            self::REGISTRY_EXTRACT => 'Government Registry Extract',
            self::COURT_FILING => 'Legal Indictment / Court Filing',
            self::INTERVIEW_TRANSCRIPT => 'Crew / Witness Interview Transcript',
            self::FINANCIAL_RECORD => 'Financial Transaction / Invoice Record',
            self::CUSTOMS_MANIFEST => 'Customs Cargo / Landing Manifest',
            self::FIELD_REPORT => 'Investigator Field Report',
        };
    }
}
