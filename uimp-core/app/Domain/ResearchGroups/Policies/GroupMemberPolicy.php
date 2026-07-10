<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * GroupMemberPolicy
 *
 * RBAC authorization rules for GroupMembership.
 *
 * NOTE: FR-MEM-008 ("PI may delegate limited Co-I administrative
 * rights") is not represented anywhere in the group_memberships
 * schema (§4.2.2 — no delegation column exists). addMember() is
 * therefore restricted to the group's PI and research_admin only,
 * pending a full design of the delegation feature (confirmed
 * decision — not an invented restriction).
 *
 * SDD Reference: RGMS SDD §3.2.10
 */
final class GroupMemberPolicy
{
    /**
     * The group's PI, Co-I, any active group member, research_admin,
     * or auditor may view membership records.
     */
    public function view(UimpAuthenticatable $user, GroupMembership $membership): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return GroupMembership::query()
            ->where('group_id', $membership->group_id)
            ->where('member_uimp_id', $user->id)
            ->exists();
    }

    /**
     * Only the group's PI or research_admin may add a new member
     * (Co-I delegation deferred — see class-level note).
     */
    public function addMember(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $this->isGroupPi($user, $group) || $this->hasRole($user, 'research_admin');
    }

    /**
     * The group's PI or research_admin may update a membership.
     */
    public function update(UimpAuthenticatable $user, GroupMembership $membership): bool
    {
        return $this->isGroupPi($user, $membership->researchGroup)
            || $this->hasRole($user, 'research_admin');
    }

    /**
     * The group's PI or research_admin may remove (terminate) a
     * membership.
     */
    public function removeMember(UimpAuthenticatable $user, GroupMembership $membership): bool
    {
        return $this->isGroupPi($user, $membership->researchGroup)
            || $this->hasRole($user, 'research_admin');
    }

    /**
     * research_admin or auditor may export the membership roster.
     */
    public function export(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Whether the authenticated user is the PI of the given research
     * group (via pi_staff_id, not a group_memberships row).
     */
    private function isGroupPi(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id;
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