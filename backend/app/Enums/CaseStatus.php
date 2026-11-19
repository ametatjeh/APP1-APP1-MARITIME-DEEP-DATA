<?php

namespace App\Enums;

enum CaseStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PENDING_EVIDENCE = 'pending_evidence';
    case UNDER_REVIEW = 'under_review';
    case CLOSED = 'closed';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft Hypothesis',
            self::ACTIVE => 'Active Investigation',
            self::PENDING_EVIDENCE => 'Pending Evidence Gathering',
            self::UNDER_REVIEW => 'Under Supervisory Review',
            self::CLOSED => 'Closed / Conclusion Finalized',
            self::ARCHIVED => 'Archived Dossier',
        };
    }
}
