<?php

namespace App\Enums;

enum EvidenceVerificationStatus: string
{
    case PENDING_VERIFICATION = 'pending_verification';
    case UNDER_REVIEW = 'under_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case CONFLICTING = 'conflicting';
    case NEEDS_ADDITIONAL_EVIDENCE = 'needs_additional_evidence';

    public function label(): string
    {
        return match($this) {
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::UNDER_REVIEW => 'Under Active Review',
            self::VERIFIED => 'Verified & Authenticated',
            self::REJECTED => 'Rejected / Inauthentic',
            self::CONFLICTING => 'Conflicting with Verified Records',
            self::NEEDS_ADDITIONAL_EVIDENCE => 'Needs Additional Corroborating Evidence',
        };
    }
}
