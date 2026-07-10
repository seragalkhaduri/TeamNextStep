<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\EquipmentAssignment;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * EquipmentAssignmentPolicy
 *
 * RBAC authorization rules for EquipmentAssignment (SDD §3.9.8):
 * view(): group members owning the equipment, admin, auditor;
 * create(): active member of any group that owns the equipment;
 * cancel(): the requester themselves (own booking) or research_admin;
 * availability(): any authenticated user.
 *
 * SDD Reference: RGMS SDD §3.9.8
 */
final class EquipmentAssignmentPolicy
{
    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a booking.
     */
    public function view(UimpAuthenticatable $user, EquipmentAssignment $booking): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $booking->researchEquipment->research_group_id);
    }

    /**
     * Any active member of the research group that owns the equipment
     * may create a booking.
     */
    public function create(UimpAuthenticatable $user, ResearchEquipment $equipment): bool
    {
        return $this->isGroupMember($user, $equipment->research_group_id);
    }

    /**
     * The requester themselves (own booking) or research_admin may
     * cancel a booking.
     */
    public function cancel(UimpAuthenticatable $user, EquipmentAssignment $booking): bool
    {
        return $booking->requester_uimp_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Any authenticated user may view the availability calendar.
     */
    public function availability(UimpAuthenticatable $user): bool
    {
        return true;
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