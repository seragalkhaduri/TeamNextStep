<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * DashboardPolicy
 *
 * RBAC authorization rules for the Dashboard module (SDD §3.13.2:
 * each dashboard variant is restricted to its corresponding role).
 *
 * SDD Reference: RGMS SDD §3.13.2
 */
final class DashboardPolicy
{
    /**
     * The group's own PI, or research_admin, may view the PI
     * dashboard for that group.
     */
    public function viewPiDashboard(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may view the admin dashboard.
     */
    public function viewAdminDashboard(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only auditor may view the auditor dashboard.
     */
    public function viewAuditorDashboard(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'auditor');
    }

    /**
     * Only research_admin may view the system administrator dashboard.
     */
    public function viewSysAdminDashboard(UimpAuthenticatable $user): bool
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