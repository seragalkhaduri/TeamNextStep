<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Models\ComplianceRecord;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\ProjectMilestone;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\EquipmentAssignmentRepository;
use Illuminate\Support\Facades\Cache;

/**
 * DashboardService
 *
 * Aggregates the four role-specific dashboard metric/alert sets (SDD
 * §3.13.2), each cached in Redis for 60 seconds — same TTL as the
 * original Livewire mount()/wire:poll.60s pattern, applied here to a
 * REST endpoint instead (module converted from Livewire to REST API
 * per your decision, for consistency with the rest of RGMS).
 *
 * SDD Reference: RGMS SDD §3.13.2, §3.13.4
 */
final class DashboardService
{
    private const TTL_SECONDS = 60;

    public function __construct(
        private readonly EquipmentAssignmentRepository $assignmentRepository,
    ) {
    }

    /**
     * PI dashboard: group status, active projects, overdue
     * milestones, budget utilization, recent publications, member
     * count, non-compliant conditions (SDD §3.13.2).
     *
     * @return array<string, mixed>
     */
    public function getPiDashboard(ResearchGroup $group): array
    {
        return Cache::remember("dashboard:pi:{$group->id}", self::TTL_SECONDS, function () use ($group): array {
            $activeProjects = $group->researchProjects()->where('status', ResearchProject::STATUS_ACTIVE)->get();

            $overdueMilestones = ProjectMilestone::query()
                ->whereIn('project_id', $activeProjects->pluck('id'))
                ->where('status', ProjectMilestone::STATUS_OVERDUE)
                ->count();

            $totalBudget = (float) $activeProjects->sum('budget');
            $totalSpent = (float) \App\Models\BudgetExpenditure::query()
                ->whereIn('project_id', $activeProjects->pluck('id'))
                ->sum('amount');

            return [
                'group_status' => $group->status,
                'active_projects_count' => $activeProjects->count(),
                'overdue_milestones_count' => $overdueMilestones,
                'budget_utilization' => $totalBudget > 0 ? round($totalSpent / $totalBudget, 4) : 0.0,
                'recent_publications_count' => $group->publications()->where('created_at', '>=', now()->subDays(30))->count(),
                'member_count' => $group->groupMemberships()->where('status', GroupMembership::STATUS_ACTIVE)->count(),
                'non_compliant_conditions' => ComplianceRecord::query()
                    ->whereHas('researchProject', fn ($q) => $q->where('research_group_id', $group->id))
                    ->where('status', ComplianceRecord::STATUS_NON_COMPLIANT)
                    ->count(),
            ];
        });
    }

    /**
     * research_admin dashboard: all-groups overview, non-compliant
     * projects, system-wide overdue milestones, total budget
     * utilization, pending equipment bookings, recent audit events.
     *
     * @return array<string, mixed>
     */
    public function getAdminDashboard(): array
    {
        return Cache::remember('dashboard:admin', self::TTL_SECONDS, function (): array {
            $totalBudget = (float) ResearchProject::query()->sum('budget');
            $totalSpent = (float) \App\Models\BudgetExpenditure::query()->sum('amount');

            return [
                'total_groups' => ResearchGroup::query()->count(),
                'active_groups' => ResearchGroup::query()->where('status', ResearchGroup::STATUS_ACTIVE)->count(),
                'non_compliant_projects' => ComplianceRecord::query()
                    ->where('status', ComplianceRecord::STATUS_NON_COMPLIANT)
                    ->distinct('project_id')
                    ->count('project_id'),
                'overdue_milestones_count' => ProjectMilestone::query()
                    ->where('status', ProjectMilestone::STATUS_OVERDUE)
                    ->count(),
                'total_budget_utilization' => $totalBudget > 0 ? round($totalSpent / $totalBudget, 4) : 0.0,
                'pending_equipment_bookings' => \App\Models\EquipmentAssignment::query()
                    ->where('status', \App\Models\EquipmentAssignment::STATUS_CONFIRMED)
                    ->where('start_datetime', '>', now())
                    ->count(),
                'recent_audit_events' => \App\Models\LocalAuditEntry::query()
                    ->orderByDesc('recorded_at')
                    ->limit(10)
                    ->get(['id', 'action', 'entity_type', 'user_id', 'recorded_at']),
            ];
        });
    }

    /**
     * Auditor dashboard: compliance status summary, non-compliant
     * list, recent audit events, budget threshold violations,
     * over-allocated members.
     *
     * @return array<string, mixed>
     */
    public function getAuditorDashboard(): array
    {
        return Cache::remember('dashboard:auditor', self::TTL_SECONDS, function (): array {
            return [
                'compliance_by_status' => ComplianceRecord::query()
                    ->selectRaw('status, COUNT(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
                'non_compliant_conditions' => ComplianceRecord::query()
                    ->where('status', ComplianceRecord::STATUS_NON_COMPLIANT)
                    ->get(['id', 'project_id', 'condition_type', 'due_date']),
                'recent_audit_events' => \App\Models\LocalAuditEntry::query()
                    ->orderByDesc('recorded_at')
                    ->limit(10)
                    ->get(['id', 'action', 'entity_type', 'user_id', 'recorded_at']),
                'over_allocated_members' => GroupMembership::query()
                    ->where('status', GroupMembership::STATUS_ACTIVE)
                    ->selectRaw('member_uimp_id, SUM(workload_percentage) as total_workload')
                    ->groupBy('member_uimp_id')
                    ->havingRaw('SUM(workload_percentage) > 100')
                    ->get(),
            ];
        });
    }

    /**
     * research_admin (System Administrator role) dashboard: UIMP API
     * connectivity, queue job counts, RGMS API response metrics,
     * active user sessions, storage utilization.
     *
     * @return array<string, mixed>
     */
    public function getSysAdminDashboard(): array
    {
        return Cache::remember('dashboard:sysadmin', self::TTL_SECONDS, function (): array {
            return [
                'uimp_api_status' => $this->checkUimpConnectivity(),
                'pending_jobs' => \Illuminate\Support\Facades\DB::table('jobs')->count(),
                'failed_jobs' => \Illuminate\Support\Facades\DB::table('failed_jobs')->count(),
                'storage_used_bytes' => $this->getStorageUsage(),
            ];
        });
    }

    private function checkUimpConnectivity(): string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get(config('services.uimp.base_url') . '/api/v1/health');

            return $response->successful() ? 'connected' : 'degraded';
        } catch (\Throwable) {
            return 'unreachable';
        }
    }

    private function getStorageUsage(): int
    {
        return (int) \Illuminate\Support\Facades\Storage::size('reports') ?: 0;
    }
}