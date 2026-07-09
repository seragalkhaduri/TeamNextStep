<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * ProjectPolicy
 *
 * RBAC authorization rules for ResearchProject.
 *
 * SDD Reference: RGMS SDD §3.3.10
 */
final class ProjectPolicy
{
    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a project.
     */
    public function view(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $project->research_group_id);
    }
/**
     * Only research_admin or auditor may view the cross-group global
     * project list (SDD §3.3.4: GET /api/v1/projects — "Admin / Auditor").
     */
    public function viewAny(UimpAuthenticatable $user): bool
    {
        return $this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor');
    }
    /**
     * The owning group's PI or research_admin may create a project.
     */
    public function create(UimpAuthenticatable $user, ResearchGroup $group): bool
    {
        return $group->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning group's PI or research_admin may update a project.
     */
    public function update(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $project->researchGroup->pi_staff_id === $user->id || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may transition a project's status.
     */
    public function transition(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may delete (soft delete) a project.
     */
    public function delete(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may generate a project report.
     */
    public function generateReport(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $this->view($user, $project);
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