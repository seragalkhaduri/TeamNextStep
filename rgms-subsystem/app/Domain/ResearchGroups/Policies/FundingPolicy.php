<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * FundingPolicy
 *
 * RBAC authorization rules for FundingSource and expenditure/budget
 * actions, derived from the Auth/Policy column of the Routes table
 * (SDD §3.5.4).
 *
 * SDD Reference: RGMS SDD §3.5.4
 */
final class FundingPolicy
{
    /**
     * Only research_admin or auditor may list all funding sources
     * (SDD §3.5.4: GET /funding-sources).
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Only research_admin may register a new funding source.
     */
    public function create(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning research group's PI, research_admin, or auditor may
     * view a funding source.
     */
    public function view(UimpAuthenticatable $user, FundingSource $fundingSource): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $fundingSource->researchGroup->pi_staff_id === $user->id;
    }

    /**
     * Only research_admin may update a funding source.
     */
    public function update(UimpAuthenticatable $user, FundingSource $fundingSource): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning project's group PI or research_admin may record an
     * expenditure.
     */
    public function createExpenditure(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $project->researchGroup->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a project's expenditures / budget summary.
     */
    public function viewProjectFinancials(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return GroupMembership::query()
            ->where('group_id', $project->research_group_id)
            ->where('member_uimp_id', $user->id)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Only research_admin may view the aggregate financial dashboard
     * (SDD §3.5.4: GET /funding-sources/dashboard — "Admin only").
     */
    public function viewDashboard(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Check whether the authenticated user's JWT roles[] claim
     * contains the given role.
     */
    private function hasRole(UimpAuthenticatable $user, string $role): bool
    {
        return in_array($role, $user->roles ?? [], true);
    }
}