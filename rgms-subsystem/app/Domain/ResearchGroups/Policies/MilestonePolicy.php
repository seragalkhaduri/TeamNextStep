<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Policies;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ProjectDeliverable;
use App\Domain\ResearchGroups\Models\ProjectMilestone;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Support\UimpAuthenticatable;

/**
 * MilestonePolicy
 *
 * RBAC authorization rules for ProjectMilestone and ProjectDeliverable,
 * derived from the Auth/Policy column of the Routes table (SDD §3.4.4).
 *
 * SDD Reference: RGMS SDD §3.4.4
 */
final class MilestonePolicy
{
    /**
     * Any active member of the owning research group, research_admin,
     * or auditor may view a milestone.
     */
    public function view(UimpAuthenticatable $user, ProjectMilestone $milestone): bool
    {
        if ($this->hasRole($user, 'research_admin') || $this->hasRole($user, 'auditor')) {
            return true;
        }

        return $this->isGroupMember($user, $milestone->researchProject);
    }

    /**
     * The owning project's group PI or research_admin may create a
     * milestone.
     */
    public function create(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $this->isProjectGroupPi($user, $project) || $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning project's group PI or research_admin may update a
     * milestone.
     */
    public function update(UimpAuthenticatable $user, ProjectMilestone $milestone): bool
    {
        return $this->isProjectGroupPi($user, $milestone->researchProject) || $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning project's group PI or research_admin may mark a
     * milestone complete.
     */
    public function complete(UimpAuthenticatable $user, ProjectMilestone $milestone): bool
    {
        return $this->isProjectGroupPi($user, $milestone->researchProject) || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may delete a milestone.
     */
    public function delete(UimpAuthenticatable $user, ProjectMilestone $milestone): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * The owning project's group PI or research_admin may create a
     * deliverable.
     */
    public function createDeliverable(UimpAuthenticatable $user, ProjectMilestone $milestone): bool
    {
        return $this->isProjectGroupPi($user, $milestone->researchProject) || $this->hasRole($user, 'research_admin');
    }

    /**
     * Only research_admin may approve or reject a deliverable.
     */
    public function approve(UimpAuthenticatable $user, ProjectDeliverable $deliverable): bool
    {
        return $this->hasRole($user, 'research_admin');
    }

    /**
     * Whether the authenticated user is the PI of the project's
     * owning research group.
     */
    private function isProjectGroupPi(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return $project->researchGroup->pi_staff_id === $user->id;
    }

    /**
     * Whether the authenticated user is an active member of the
     * project's owning research group.
     */
    private function isGroupMember(UimpAuthenticatable $user, ResearchProject $project): bool
    {
        return GroupMembership::query()
            ->where('group_id', $project->research_group_id)
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