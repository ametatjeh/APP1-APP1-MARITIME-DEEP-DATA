<?php

namespace App\Enums;

enum CustodyAction: string
{
    case COLLECTED = 'collected';
    case UPLOADED = 'uploaded';
    case HASH_GENERATED = 'hash_generated';
    case METADATA_UPDATED = 'metadata_updated';
    case ACCESSED = 'accessed';
    case DOWNLOADED = 'downloaded';
    case TRANSFERRED = 'transferred';
    case VERIFIED = 'verified';
    case REDACTED = 'redacted';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::COLLECTED => 'Initial Evidence Collection',
            self::UPLOADED => 'Uploaded to Secure Storage',
            self::HASH_GENERATED => 'SHA-256 Checksum Computed',
            self::METADATA_UPDATED => 'Metadata / Attribution Edited',
            self::ACCESSED => 'Viewed / Read by Analyst',
            self::DOWNLOADED => 'Binary Stream Downloaded',
            self::TRANSFERRED => 'Chain of Custody Transferred',
            self::VERIFIED => 'Analyst Formal Verification Completed',
            self::REDACTED => 'PII / Sensitive Content Redacted',
            self::ARCHIVED => 'Placed in Long-Term Archive',
        };
    }
}
