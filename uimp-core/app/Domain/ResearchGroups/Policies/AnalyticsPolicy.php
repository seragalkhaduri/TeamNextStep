<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * AnalyticsPolicy
 *
 * RBAC authorization rules for the Analytics module, derived from
 * the Auth/Policy column of the Routes table (SDD §3.11.4).
 *
 * SDD Reference: RGMS SDD §3.11.4
 */
final class AnalyticsPolicy
{
    /**
     * Only research_admin or auditor may view platform-wide
     * productivity metrics.
     */
    public function viewProductivity(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Only research_admin or auditor may view platform-wide trends.
     */
    public function viewTrends(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * Only research_admin or auditor may view cross-group comparisons.
     */
    public function viewComparisons(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * The group's own PI, or research_admin, may view a single
     * group's statistics (SDD §3.11.4: "PI (own group) / Admin").
     */
    public function viewGroupStats(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
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
