<?php

namespace App\Policies;

use App\Enums\EvidenceClassification;
use App\Enums\UserRole;
use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Evidence $evidence): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::RESEARCH_DIRECTOR, UserRole::EVIDENCE_MANAGER])) {
            return true;
        }

        // If evidence is linked to a restricted case, check case access
        if ($evidence->case) {
            $casePolicy = new InvestigationCasePolicy();
            if (!$casePolicy->view($user, $evidence->case)) {
                return false;
            }
        }

        // Strictly restricted evidence requires senior clearance
        if ($evidence->classification === EvidenceClassification::STRICTLY_RESTRICTED) {
            return in_array($user->role, [
                UserRole::SUPER_ADMIN,
                UserRole::RESEARCH_DIRECTOR,
                UserRole::SENIOR_INVESTIGATOR,
                UserRole::EVIDENCE_MANAGER,
            ]);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::RESEARCH_DIRECTOR,
            UserRole::SENIOR_INVESTIGATOR,
            UserRole::INVESTIGATOR,
            UserRole::RESEARCH_ANALYST,
            UserRole::EVIDENCE_MANAGER,
        ]);
    }

    public function download(User $user, Evidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }

    public function verify(User $user, Evidence $evidence): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::RESEARCH_DIRECTOR,
            UserRole::SENIOR_INVESTIGATOR,
            UserRole::EVIDENCE_MANAGER,
        ]);
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::RESEARCH_DIRECTOR,
        ]);
    }
}
