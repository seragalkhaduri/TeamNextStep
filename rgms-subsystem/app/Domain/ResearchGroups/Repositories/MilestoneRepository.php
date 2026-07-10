<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ProjectMilestone;
use Illuminate\Support\Collection;

/**
 * MilestoneRepository
 *
 * @extends BaseRepository<ProjectMilestone>
 */
final class MilestoneRepository extends BaseRepository
{
    public function __construct(ProjectMilestone $model)
    {
        parent::__construct($model);
    }

    /**
     * All milestones for a single project, ordered by due date.
     */
    public function findByProject(string $projectId): Collection
    {
        return $this->model->newQuery()
            ->where('project_id', $projectId)
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Whether every milestone on a project is Completed (used by
     * MilestoneService::complete() to suggest a project status
     * transition).
     */
    public function allCompleteForProject(string $projectId): bool
    {
        return ! $this->model->newQuery()
            ->where('project_id', $projectId)
            ->where('status', '!=', ProjectMilestone::STATUS_COMPLETED)
            ->exists();
    }

    /**
     * Efficient single bulk UPDATE transitioning all past-due,
     * still-open milestones to Overdue (SDD §3.4.8 — literal query).
     * Returns the affected milestones for downstream notification
     * dispatch.
     */
    public function bulkDetectOverdue(): Collection
    {
        $overdue = $this->model->newQuery()
            ->whereIn('status', [ProjectMilestone::STATUS_PENDING, ProjectMilestone::STATUS_IN_PROGRESS])
            ->where('due_date', '<', now())
            ->get();

        $this->model->newQuery()
            ->whereIn('status', [ProjectMilestone::STATUS_PENDING, ProjectMilestone::STATUS_IN_PROGRESS])
            ->where('due_date', '<', now())
            ->update(['status' => ProjectMilestone::STATUS_OVERDUE]);

        return $overdue;
    }
}