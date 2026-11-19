<?php

namespace App\Policies;

use App\Enums\CaseConfidentiality;
use App\Enums\UserRole;
use App\Models\InvestigationCase;
use App\Models\User;

class InvestigationCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, InvestigationCase $case): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // Super Admin and Director can view all cases
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::RESEARCH_DIRECTOR])) {
            return true;
        }

        // Standard confidentiality cases can be viewed by all research staff
        if ($case->confidentiality_level === CaseConfidentiality::STANDARD) {
            return true;
        }

        // Restricted or Secret cases require direct case assignment or Senior Investigator rank
        if ($user->role === UserRole::SENIOR_INVESTIGATOR) {
            return true;
        }

        return $case->isUserAssigned($user->id);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::RESEARCH_DIRECTOR,
            UserRole::SENIOR_INVESTIGATOR,
            UserRole::INVESTIGATOR,
        ]);
    }

    public function update(User $user, InvestigationCase $case): bool
    {
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::RESEARCH_DIRECTOR, UserRole::SENIOR_INVESTIGATOR])) {
            return true;
        }

        if ($user->role === UserRole::INVESTIGATOR) {
            return $case->isUserAssigned($user->id);
        }

        return false;
    }

    public function delete(User $user, InvestigationCase $case): bool
    {
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::RESEARCH_DIRECTOR]);
    }

    public function assign(User $user, InvestigationCase $case): bool
    {
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::RESEARCH_DIRECTOR, UserRole::SENIOR_INVESTIGATOR])) {
            return true;
        }

        return $case->lead_investigator_id === $user->id;
    }
}
