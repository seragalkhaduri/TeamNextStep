<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * ReportPolicy
 *
 * RBAC authorization rules for the Reporting Engine, derived from
 * the Auth/Policy column of the Routes table (SDD §3.12.4).
 *
 * SDD Reference: RGMS SDD §3.12.4
 */
final class ReportPolicy
{
    /**
     * Any authenticated user may request report generation — the
     * RBAC scope itself is narrowed at the service layer
     * (ReportService::intersectWithAuthorizedScope()), not here.
     */
    public function generate(UimpAuthenticatable $user): bool
    {
        return true;
    }

    /**
     * Only research_admin or auditor may view the full execution
     * history (SDD §3.12.4: GET /reports/history — "Admin / Auditor").
     */
    public function viewHistory(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }

    /**
     * The user who generated the report, or research_admin, may
     * download it.
     */
    public function download(UimpAuthenticatable $user, ReportExecutionHistory $history): bool
    {
        return $history->generated_by === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may manage report schedules.
     */
    public function manageSchedules(UimpAuthenticatable $user): bool
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