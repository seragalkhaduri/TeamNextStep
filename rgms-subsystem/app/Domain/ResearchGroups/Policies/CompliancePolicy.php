<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * CompliancePolicy
 *
 * RBAC authorization rules for ComplianceRecord, derived from the
 * Auth/Policy column of the Routes table (SDD §3.10.4).
 *
 * SDD Reference: RGMS SDD §3.10.4
 */
final class CompliancePolicy
{
    /**
     * Only research_admin or auditor may list a project's compliance
     * conditions (SDD §3.10.4: GET /projects/{pid}/compliance —
     * "Admin / Auditor").
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Only research_admin may create a compliance condition.
     */
    public function create(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a single compliance record.
     */
    public function view(UimpAuthenticatable $user, ComplianceRecord $record): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return GroupMembership::query()
            ->where('group_id', $record->researchProject->research_group_id)
            ->where('member_uimp_id', $user->id)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Only research_admin may update a compliance record.
     */
    public function update(UimpAuthenticatable $user, ComplianceRecord $record): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may resolve a compliance condition.
     */
    public function resolve(UimpAuthenticatable $user, ComplianceRecord $record): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin or auditor may view the compliance
     * dashboard (SDD §3.10.4).
     */
    public function viewDashboard(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
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