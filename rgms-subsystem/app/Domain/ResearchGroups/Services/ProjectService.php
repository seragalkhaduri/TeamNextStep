<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;
use Illuminate\Support\Facades\Cache;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ComplianceRecordRepository;
use App\Domain\ResearchGroups\Repositories\ProjectRepository;
use App\Domain\ResearchGroups\Services\Support\ProjectStateMachine;
use Illuminate\Support\Facades\DB;

/**
 * ProjectService
 *
 * Implements all business rules for the Research Projects Management
 * module: group-budget validation on creation, state-machine-governed
 * lifecycle transitions (BR-002 readiness check), and daily
 * compliance condition monitoring.
 *
 * SDD Reference: RGMS SDD §3.3.2, §3.3.7
 */
final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $repository,
        private readonly ComplianceRecordRepository $complianceRepository,
        private readonly ProjectStateMachine $stateMachine,
    ) {
    }

    /**
     * Create a new research project.
     *
     * Validates that the project's budget does not exceed the
     * owning group's remaining budget (group.budget_allocation minus
     * the sum of that group's other projects' budgets).
     *
     * @param array<string, mixed> $data
     */
    public function create(ResearchGroup $group, array $data): ResearchProject
    {
        $this->assertBudgetWithinGroupAllocation($group, (float) $data['budget']);

        return DB::transaction(function () use ($group, $data): ResearchProject {
            $project = $this->repository->create([
                ...$data,
                'research_group_id' => $group->id,
                'status' => ResearchProject::STATUS_PLANNING,
            ]);

            AuditLog::record('CREATE', 'research_projects', $project->id, null, $data);

            dispatch(new SendUimpNotification(
                [$group->pi_staff_id],
                'project.created',
                ['entity_id' => $project->id, 'group_id' => $group->id],
            ));

            return $project;
        });
    }

    /**
     * Update mutable attributes of a project (research_group_id and
     * status are excluded — see BelongsToAuthScope/transition()).
     *
     * @param array<string, mixed> $data
     */
    public function update(ResearchProject $project, array $data): ResearchProject
    {
        $oldValues = $project->only(array_keys($data));

        return DB::transaction(function () use ($project, $data, $oldValues): ResearchProject {
            $updated = $this->repository->update($project, $data);

            AuditLog::record('UPDATE', 'research_projects', $updated->id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Transition a project to a new lifecycle status.
     *
     * On Planning -> Active, enforces BR-002: at least one milestone
     * must be defined and the owning group must have an assigned PI.
     * On Completed/Terminated, notifies the PI and all Co-Is.
     */
    public function transition(ResearchProject $project, string $newStatus, ?string $reason): ResearchProject
    {
        $currentStatus = $project->status;

        $this->stateMachine->validateTransition($currentStatus, $newStatus);

        if ($currentStatus === ResearchProject::STATUS_PLANNING && $newStatus === ResearchProject::STATUS_ACTIVE) {
            $this->assertGroupReadyForActivation($project);
        }

        return DB::transaction(function () use ($project, $currentStatus, $newStatus, $reason): ResearchProject {
            $updated = $this->repository->update($project, ['status' => $newStatus]);

            AuditLog::record(
                'TRANSITION',
                'research_projects',
                $updated->id,
                ['status' => $currentStatus],
                ['status' => $newStatus, 'reason' => $reason],
            );

            if (in_array($newStatus, [ResearchProject::STATUS_COMPLETED, ResearchProject::STATUS_TERMINATED], true)) {
                $recipients = $updated->projectContributors()->pluck('member_uimp_id')->push($updated->researchGroup->pi_staff_id)->unique()->values()->all();

                dispatch(new SendUimpNotification(
                    $recipients,
                    'project.status_changed',
                    ['entity_id' => $updated->id, 'old_status' => $currentStatus, 'new_status' => $newStatus],
                ));
            }
Cache::tags(['analytics:projects'])->flush();
            return $updated;
        });
    }

    /**
     * Soft delete a research project.
     */
    public function softDelete(ResearchProject $project): bool
    {
        return DB::transaction(function () use ($project): bool {
            $result = $this->repository->softDelete($project);

            AuditLog::record('DELETE', 'research_projects', $project->id, $project->toArray(), null);

            return $result;
        });
    }

    /**
     * Verify BR-004: reject expenditure/budget mutation against
     * Terminated or Completed projects. Exposed for use by
     * BudgetExpenditureService (Module 3.5).
     */
    public function assertMutable(ResearchProject $project): void
    {
        if (in_array($project->status, [ResearchProject::STATUS_COMPLETED, ResearchProject::STATUS_TERMINATED], true)) {
            throw new ConflictException(
                'BR-004 violation: cannot record financial activity against a Completed or Terminated project.',
            );
        }
    }

    /**
     * Daily compliance sweep (Laravel Scheduler, 01:00 — SDD §3.3.13).
     * Queries all active projects, evaluates their compliance
     * records, and alerts on any Non-Compliant condition found.
     */
    public function checkComplianceConditions(): void
    {
        $activeProjects = $this->repository->paginateGlobal(['status' => ResearchProject::STATUS_ACTIVE], perPage: 500);

        foreach ($activeProjects as $project) {
            $nonCompliantRecords = $this->complianceRepository->findNonCompliantByProject($project->id);

            foreach ($nonCompliantRecords as $record) {
                $this->alertNonCompliance($project, $record);
            }
        }
    }

    /**
     * Dispatch a non-compliance alert notification to the project's
     * PI (FR-PROJ-007).
     */
    private function alertNonCompliance(ResearchProject $project, \App\Models\ComplianceRecord $record): void
    {
        dispatch(new SendUimpNotification(
            [$project->researchGroup->pi_staff_id],
            'project.compliance_alert',
            ['entity_id' => $project->id, 'condition_type' => $record->condition_type],
        ));
    }

    /**
     * Assert that a project's budget does not exceed the owning
     * group's remaining budget allocation.
     */
    private function assertBudgetWithinGroupAllocation(ResearchGroup $group, float $projectBudget): void
    {
        if ($group->budget_allocation === null) {
            return;
        }

        $committedBudget = (float) $group->researchProjects()
            ->whereNotIn('status', [ResearchProject::STATUS_TERMINATED])
            ->sum('budget');

        $remaining = (float) $group->budget_allocation - $committedBudget;

        if ($projectBudget > $remaining) {
            throw new ConflictException(
                sprintf('Project budget (%.2f) exceeds the group\'s remaining budget allocation (%.2f).', $projectBudget, $remaining),
            );
        }
    }

    /**
     * Assert BR-002: the project has at least one milestone and its
     * owning group has an assigned PI, required before Planning ->
     * Active.
     */
    private function assertGroupReadyForActivation(ResearchProject $project): void
    {
        $milestoneCount = $project->projectMilestones()->count();

        if ($milestoneCount === 0) {
            throw new ConflictException(
                'BR-002 violation: at least one milestone must be defined before activating a project.',
            );
        }

        if (blank($project->researchGroup->pi_staff_id)) {
            throw new ConflictException(
                'BR-002 violation: the owning research group must have an assigned PI before activating a project.',
            );
        }
    }
}