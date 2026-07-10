<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\ReportExecutionHistory;
use App\Domain\ResearchGroups\Models\ReportSchedule;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ReportRepository
 *
 * Provides the data-loading query for each of the seven report types
 * (SDD §3.12.6), plus pagination for execution history and schedules.
 *
 * SDD Reference: RGMS SDD §3.12.3, §3.12.5
 */
final class ReportRepository
{
    /**
     * Load the dataset for a given report type, scoped by
     * (RBAC-intersected) group_ids and an optional date range.
     *
     * @param array<string, mixed> $filters
     */
    public function loadData(string $reportType, array $filters): Collection
    {
        return match ($reportType) {
            'ResearchGroupSummary' => $this->researchGroupSummary($filters),
            'ProjectProgress' => $this->projectProgress($filters),
            'BudgetUtilization' => $this->budgetUtilization($filters),
            'MembershipRoster' => $this->membershipRoster($filters),
            'PublicationOutput' => $this->publicationOutput($filters),
            'AssetInventory' => $this->assetInventory($filters),
            'ComplianceStatus' => $this->complianceStatus($filters),
            default => collect(),
        };
    }

    public function findExecutionHistory(string $id): ReportExecutionHistory
    {
        return ReportExecutionHistory::query()->findOrFail($id);
    }

    public function paginateHistory(int $perPage = 15): LengthAwarePaginator
    {
        return ReportExecutionHistory::query()
            ->orderByDesc('generated_at')
            ->paginate($perPage);
    }

    public function createSchedule(array $data): ReportSchedule
    {
        return ReportSchedule::create($data);
    }

    public function findSchedules(): Collection
    {
        return ReportSchedule::query()->where('is_active', true)->get();
    }

    public function deleteSchedule(ReportSchedule $schedule): bool
    {
        return (bool) $schedule->delete();
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function scopeGroupIds(array $filters): array
    {
        return $filters['group_ids'] ?? [];
    }

    private function researchGroupSummary(array $filters): Collection
    {
        $query = ResearchGroup::query()->with(['groupMemberships', 'researchProjects']);

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function projectProgress(array $filters): Collection
    {
        $query = ResearchProject::query()->with('projectMilestones');

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('research_group_id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function budgetUtilization(array $filters): Collection
    {
        $query = ResearchProject::query()->with(['researchGroup']);

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('research_group_id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function membershipRoster(array $filters): Collection
    {
        $query = GroupMembership::query()->where('status', GroupMembership::STATUS_ACTIVE);

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('group_id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function publicationOutput(array $filters): Collection
    {
        $query = Publication::query()->with('publicationAuthors');

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('research_group_id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function assetInventory(array $filters): Collection
    {
        $query = ResearchEquipment::query();

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereIn('research_group_id', $this->scopeGroupIds($filters));
        }

        return $query->get();
    }

    private function complianceStatus(array $filters): Collection
    {
        $query = ComplianceRecord::query()->with('researchProject');

        if (! empty($this->scopeGroupIds($filters))) {
            $query->whereHas('researchProject', function ($q) use ($filters): void {
                $q->whereIn('research_group_id', $this->scopeGroupIds($filters));
            });
        }

        return $query->get();
    }
}