<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * EquipmentPolicy
 *
 * RBAC authorization rules for ResearchEquipment, derived from the
 * Auth/Policy column of the Routes table (SDD §3.8.4).
 *
 * SDD Reference: RGMS SDD §3.8.4
 */
final class EquipmentPolicy
{
    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view an equipment record.
     */
    public function view(UimpAuthenticatable $user, ResearchEquipment $equipment): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $equipment->research_group_id);
    }

    /**
     * Only research_admin may register a new equipment asset (SDD
     * §3.8.4: "create Policy (Admin)").
     */
    public function create(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may update an equipment asset (including
     * status transitions and decommissioning).
     */
    public function update(UimpAuthenticatable $user, ResearchEquipment $equipment): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Any active member of the owning research group, or
     * research_admin, may log a maintenance event (SDD §3.8.4:
     * "maintain Policy" — no (Admin) restriction noted, unlike other
     * actions in this table).
     */
    public function maintain(UimpAuthenticatable $user, ResearchEquipment $equipment): bool
    {
        if ($this->hasRole($user, 'research_admin')) {
            return true;
        }

        return $this->isGroupMember($user, $equipment->research_group_id);
    }

    /**
     * Only research_admin may delete an equipment asset.
     */
    public function delete(UimpAuthenticatable $user, ResearchEquipment $equipment): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin or auditor may view the cross-group global
     * equipment list (SDD §3.8.4: GET /equipment — "Admin / Auditor").
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
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