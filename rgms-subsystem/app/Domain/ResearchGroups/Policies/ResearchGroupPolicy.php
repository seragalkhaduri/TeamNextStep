<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * ResearchGroupPolicy
 *
 * RBAC authorization rules for ResearchGroup, evaluated against the
 * roles[] claim of the UIMP-issued JWT (bound to the request via
 * UimpJwtMiddleware -> UimpAuthenticatable, SDD §3.14.4).
 *
 * SDD Reference: RGMS SDD §3.1.10
 */
final class ResearchGroupPolicy
{
    /**
     * Any authenticated user may list research groups (results are
     * still scoped/filtered by the Service and Controller as needed).
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return true;
    }

    /**
     * A group member, research_admin, or auditor may view a group.
     */
    public function view(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return GroupMembership::query()
            ->where('group_id', $group->id)
            ->where('member_uimp_id', $user->id)
            ->exists();
    }

    /**
     * Only pi_role or research_admin may create a research group.
     */
    public function create(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'pi') || $this->hasRole($user, 'research_admin');
    }

    /**
     * The group's own PI (pi_staff_id matches the authenticated
     * user's ID) or research_admin may update a research group.
     */
    public function update(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may transition a research group's status.
     */
    public function transition(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may delete (soft delete) a research group.
     */
    public function delete(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * research_admin or auditor may export research group data.
     */
    public function export(UimpAuthenticatable $user): bool
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