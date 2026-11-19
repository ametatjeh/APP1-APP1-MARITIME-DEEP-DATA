<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case RESEARCH_DIRECTOR = 'research_director';
    case SENIOR_INVESTIGATOR = 'senior_investigator';
    case INVESTIGATOR = 'investigator';
    case RESEARCH_ANALYST = 'research_analyst';
    case EVIDENCE_MANAGER = 'evidence_manager';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::RESEARCH_DIRECTOR => 'Research Director',
            self::SENIOR_INVESTIGATOR => 'Senior Investigator',
            self::INVESTIGATOR => 'Investigator',
            self::RESEARCH_ANALYST => 'Research Analyst',
            self::EVIDENCE_MANAGER => 'Evidence Manager',
            self::VIEWER => 'Viewer',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function canEditIntelligence(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::RESEARCH_DIRECTOR,
            self::SENIOR_INVESTIGATOR,
            self::INVESTIGATOR,
            self::RESEARCH_ANALYST,
        ]);
    }

    public function canResolveConflicts(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::RESEARCH_DIRECTOR,
            self::SENIOR_INVESTIGATOR,
        ]);
    }

    public function canViewRedactedPII(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::RESEARCH_DIRECTOR,
            self::SENIOR_INVESTIGATOR,
        ]);
    }
}
