<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Events\ProjectAllMilestonesCompletedEvent;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\ProjectDeliverable;
use App\Domain\ResearchGroups\Models\ProjectMilestone;
use App\Domain\ResearchGroups\Repositories\MilestoneRepository;
use Illuminate\Support\Facades\DB;

/**
 * MilestoneService
 *
 * Implements all business rules for the Project Milestone Management
 * module: creation, manual completion (with all-complete detection),
 * automatic hourly overdue detection (system-only status), and
 * deliverable submission/approval workflow.
 *
 * SDD Reference: RGMS SDD §3.4.2, §3.4.6
 */
final class MilestoneService
{
    public function __construct(
        private readonly MilestoneRepository $repository,
    ) {
    }

    /**
     * Create a new project milestone.
     *
     * @param array<string, mixed> $data
     */
    public function create(string $projectId, array $data): ProjectMilestone
    {
        return DB::transaction(function () use ($projectId, $data): ProjectMilestone {
            $milestone = $this->repository->create([
                ...$data,
                'project_id' => $projectId,
                'status' => ProjectMilestone::STATUS_PENDING,
            ]);

            AuditLog::record('CREATE', 'project_milestones', $milestone->id, null, $data);

            return $milestone;
        });
    }
     /**
     * Soft delete a milestone.
     */
   /**
     * Update mutable attributes of a milestone (status is excluded —
     * governed via complete() and detectOverdue() only).
     *
     * @param array<string, mixed> $data
     */
    public function update(ProjectMilestone $milestone, array $data): ProjectMilestone
    {
        $oldValues = $milestone->only(array_keys($data));

        return DB::transaction(function () use ($milestone, $data, $oldValues): ProjectMilestone {
            $updated = $this->repository->update($milestone, $data);

            AuditLog::record('UPDATE', 'project_milestones', $updated->id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Soft delete a milestone.
     */
    public function softDelete(ProjectMilestone $milestone): bool
    {
        return DB::transaction(function () use ($milestone): bool {
            $result = $this->repository->softDelete($milestone);

            AuditLog::record('DELETE', 'project_milestones', $milestone->id, $milestone->toArray(), null);

            return $result;
        });
    }
/**
     * Update mutable attributes of a milestone (status is excluded —
     * governed via complete() and detectOverdue() only).
     *
     * @param array<string, mixed> $data
     */
    /**
     * PUT /api/v1/projects/{pid}/milestones/{mid}
     */
  
   
    /**
     * Mark a milestone as Completed. If this results in every
     * milestone on the parent project being Completed, dispatches
     * ProjectAllMilestonesCompletedEvent to suggest a project status
     * transition.
     *
     * @param array<string, mixed> $data
     */
    public function complete(ProjectMilestone $milestone, array $data): ProjectMilestone
    {
        return DB::transaction(function () use ($milestone, $data): ProjectMilestone {
            $updated = $this->repository->update($milestone, [
                'status' => ProjectMilestone::STATUS_COMPLETED,
                'completion_date' => $data['completion_date'],
                'completion_notes' => $data['completion_notes'] ?? null,
            ]);

            AuditLog::record(
                'UPDATE',
                'project_milestones',
                $updated->id,
                ['status' => $milestone->getOriginal('status')],
                ['status' => ProjectMilestone::STATUS_COMPLETED],
            );

            if ($this->repository->allCompleteForProject($updated->project_id)) {
                ProjectAllMilestonesCompletedEvent::dispatch($updated->project_id);
            }

            return $updated;
        });
    }

    /**
     * Hourly scheduled sweep (SDD §3.4.6, §3.4.8 — literal bulk
     * UPDATE pattern): transitions all past-due, still-open
     * milestones to Overdue in a single query, then dispatches a
     * notification for each affected milestone.
     */
    public function detectOverdue(): void
    {
        $overdueMilestones = $this->repository->bulkDetectOverdue();

        foreach ($overdueMilestones as $milestone) {
            AuditLog::record(
                'TRANSITION',
                'project_milestones',
                $milestone->id,
                ['status' => $milestone->status],
                ['status' => ProjectMilestone::STATUS_OVERDUE],
            );

            $project = $milestone->researchProject;

            dispatch(new SendUimpNotification(
                [$project->researchGroup->pi_staff_id],
                'milestone.overdue',
                ['entity_id' => $milestone->id, 'project_id' => $project->id],
            ));
        }
    }

    /**
     * Create a deliverable linked to a milestone.
     *
     * @param array<string, mixed> $data
     */
    public function storeDeliverable(string $milestoneId, array $data): ProjectDeliverable
    {
        return DB::transaction(function () use ($milestoneId, $data): ProjectDeliverable {
            $deliverable = ProjectDeliverable::create([
                ...$data,
                'milestone_id' => $milestoneId,
                'approval_status' => ProjectDeliverable::APPROVAL_PENDING,
            ]);

            AuditLog::record('CREATE', 'project_deliverables', $deliverable->id, null, $data);

            return $deliverable;
        });
    }

    /**
     * Approve or reject a deliverable.
     */
    public function approveDeliverable(ProjectDeliverable $deliverable, string $approvalStatus, string $approvedBy): ProjectDeliverable
    {
        return DB::transaction(function () use ($deliverable, $approvalStatus, $approvedBy): ProjectDeliverable {
            $oldStatus = $deliverable->approval_status;

            $deliverable->update([
                'approval_status' => $approvalStatus,
                'approved_by' => $approvedBy,
            ]);

            AuditLog::record(
                'UPDATE',
                'project_deliverables',
                $deliverable->id,
                ['approval_status' => $oldStatus],
                ['approval_status' => $approvalStatus],
            );

            return $deliverable->fresh();
        });
    }
}