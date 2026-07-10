<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\Patent;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * PatentPolicy
 *
 * RBAC authorization rules for Patent, derived from the Auth/Policy
 * column of the Routes table (SDD §3.7.4).
 *
 * SDD Reference: RGMS SDD §3.7.4
 */
final class PatentPolicy
{
    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a patent.
     */
    public function view(UimpAuthenticatable $user, Patent $patent): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $patent->research_group_id);
    }

    /**
     * The owning group's PI or research_admin may register a patent.
     */
    public function create(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning group's PI or research_admin may update a patent
     * (including status transitions).
     */
    public function update(UimpAuthenticatable $user, Patent $patent): bool
    {
        return $patent->researchGroup->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may delete a patent.
     */
    public function delete(UimpAuthenticatable $user, Patent $patent): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Whether the authenticated user is an active member of the
     * given research group.
     */
    private function isGroupMember(UimpAuthenticatable $user, string $groupId): bool
    {
        return GroupMembership::query()
            ->where('group_id', $groupId)
            ->where('member_uimp_id', $user->id)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->exists();
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