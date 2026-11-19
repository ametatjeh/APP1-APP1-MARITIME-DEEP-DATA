<?php

namespace App\Enums;

enum CaseRole: string
{
    case LEAD = 'lead';
    case CO_INVESTIGATOR = 'co_investigator';
    case ANALYST = 'analyst';
    case EVIDENCE_HANDLER = 'evidence_handler';
    case REVIEWER = 'reviewer';

    public function label(): string
    {
        return match($this) {
            self::LEAD => 'Lead Investigator',
            self::CO_INVESTIGATOR => 'Co-Investigator',
            self::ANALYST => 'Research Analyst',
            self::EVIDENCE_HANDLER => 'Evidence Handler',
            self::REVIEWER => 'Supervisory Reviewer',
        };
    }
}
