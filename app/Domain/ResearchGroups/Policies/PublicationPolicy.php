<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * PublicationPolicy
 *
 * RBAC authorization rules for Publication, derived from the
 * Auth/Policy column of the Routes table (SDD §3.6.4).
 *
 * SDD Reference: RGMS SDD §3.6.4
 */
final class PublicationPolicy
{
    /**
     * Only research_admin or auditor may view the cross-group global
     * publication list (SDD §3.6.4: GET /publications).
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a publication.
     */
    public function view(UimpAuthenticatable $user, Publication $publication): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $publication->research_group_id);
    }

    /**
     * The owning group's PI, a Co-I, or research_admin may register
     * a publication.
     */
    public function create(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        if ($this->hasRole($user, 'research_admin')) {
            return true;
        }

        if ($group->pi_staff_id === $user->id) {
            return true;
        }

        return GroupMembership::query()
            ->where('group_id', $group->id)
            ->where('member_uimp_id', $user->id)
            ->where('role', GroupMembership::ROLE_CO_I)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * The owning group's PI or research_admin may update a
     * publication (including status transitions and citation updates).
     */
    public function update(UimpAuthenticatable $user, Publication $publication): bool
    {
        return $publication->researchGroup->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may delete a publication.
     */
    public function delete(UimpAuthenticatable $user, Publication $publication): bool
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