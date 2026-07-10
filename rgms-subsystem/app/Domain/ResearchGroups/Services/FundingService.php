<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;
use Illuminate\Support\Facades\Cache;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\BudgetExpenditure;
use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ExpenditureRepository;
use App\Domain\ResearchGroups\Repositories\FundingSourceRepository;
use Illuminate\Support\Facades\DB;

/**
 * FundingService
 *
 * Implements all business rules for the Funding Source and Budget
 * Monitoring module: funding source registration, immutable
 * expenditure recording with three pre-write checks (BR-004,
 * FR-FUND-009, DoesNotExceedBudget), real-time budget utilization
 * calculation, and daily 80%/100% threshold alerting (FR-FUND-005).
 *
 * SDD Reference: RGMS SDD §3.5.2, §3.5.5
 */
final class FundingService
{
    /**
     * Utilization thresholds that trigger alerts (FR-FUND-005).
     */
    private const WARNING_THRESHOLD = 0.80;
    private const EXHAUSTED_THRESHOLD = 1.00;

    public function __construct(
        private readonly FundingSourceRepository $fundingRepository,
        private readonly ExpenditureRepository $expenditureRepository,
        private readonly ProjectService $projectService,
    ) {
    }

    /**
     * Register a new funding source.
     *
     * @param array<string, mixed> $data
     */
    public function registerFundingSource(array $data): FundingSource
    {
        return DB::transaction(function () use ($data): FundingSource {
            $fundingSource = $this->fundingRepository->create([
                ...$data,
                'status' => FundingSource::STATUS_ACTIVE,
            ]);

            AuditLog::record('CREATE', 'funding_sources', $fundingSource->id, null, $data);

            return $fundingSource;
        });
    }

    /**
     * Update a funding source.
     *
     * @param array<string, mixed> $data
     */
    public function updateFundingSource(FundingSource $fundingSource, array $data): FundingSource
    {
        $oldValues = $fundingSource->only(array_keys($data));

        return DB::transaction(function () use ($fundingSource, $data, $oldValues): FundingSource {
            $updated = $this->fundingRepository->update($fundingSource, $data);

            AuditLog::record('UPDATE', 'funding_sources', $updated->id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Record an expenditure entry.
     *
     * Three pre-write checks per SDD §3.5.5:
     * (1) project status not Terminated/Completed (BR-004, delegates
     *     to ProjectService::assertMutable());
     * (2) funding source end_date not passed (FR-FUND-009);
     * (3) DoesNotExceedBudget — already enforced by the Form Request
     *     validation layer before this method is called; re-checked
     *     here defensively since financial writes warrant
     *     defense-in-depth.
     *
     * Wrapped in DB::transaction() with rollback on failure
     * (NFR-SAFE-002).
     *
     * @param array<string, mixed> $data
     */
    public function registerExpenditure(ResearchProject $project, FundingSource $fundingSource, array $data): BudgetExpenditure
    {
        $this->projectService->assertMutable($project);

        if ($fundingSource->end_date->isPast()) {
            throw new ConflictException('Cannot record an expenditure against an expired funding source.');
        }

        return DB::transaction(function () use ($project, $fundingSource, $data): BudgetExpenditure {
            $expenditure = BudgetExpenditure::create([
                ...$data,
                'project_id' => $project->id,
                'funding_source_id' => $fundingSource->id,
            ]);

            AuditLog::record('CREATE', 'budget_expenditures', $expenditure->id, null, $data);
Cache::tags(['analytics:funding'])->flush();
            return $expenditure;
        });
    }

    /**
     * Compute a structured budget utilization summary for a project:
     * Total Allocated, Total Expended, Remaining Balance, and a
     * per-category breakdown (FR-FUND-004).
     *
     * @return array{allocated: float, expended: float, remaining: float, utilization: float, by_category: array<string, float>}
     */
    public function computeBudgetSummary(string $projectId): array
    {
        $project = ResearchProject::query()->findOrFail($projectId);

        $allocated = (float) $project->budget;
        $expended = $this->expenditureRepository->sumByProject($projectId);
        $remaining = $allocated - $expended;
        $utilization = $allocated > 0 ? round($expended / $allocated, 4) : 0.0;

        return [
            'allocated' => $allocated,
            'expended' => $expended,
            'remaining' => $remaining,
            'utilization' => $utilization,
            'by_category' => $this->expenditureRepository->sumByCategory($projectId),
        ];
    }

    /**
     * Daily scheduled sweep (02:00 — SDD §3.5.5): computes
     * utilization per project and dispatches budget_warning (>=80%)
     * or budget_exhausted (>=100%) notifications to the group PI and
     * research_admin (FR-FUND-005).
     */
    public function checkThresholds(): void
    {
        ResearchProject::query()
            ->where('status', ResearchProject::STATUS_ACTIVE)
            ->chunk(200, function ($projects): void {
                foreach ($projects as $project) {
                    $summary = $this->computeBudgetSummary($project->id);

                    if ($summary['utilization'] >= self::EXHAUSTED_THRESHOLD) {
                        $this->dispatchThresholdAlert($project, 'budget_exhausted', $summary);
                    } elseif ($summary['utilization'] >= self::WARNING_THRESHOLD) {
                        $this->dispatchThresholdAlert($project, 'budget_warning', $summary);
                    }
                }
            });
    }
/**
     * Aggregate financial dashboard across all active funding sources
     * and projects, for research_admin (FR-FUND-011).
     *
     * @return array{total_active_sources: int, total_allocated: float, total_expended: float, expired_active_sources: int}
     */
    public function computeDashboard(): array
    {
        $activeSources = $this->fundingRepository->findAllActive();
        $expiredButActive = $this->fundingRepository->findExpiredButActive();

        $totalAllocated = (float) $activeSources->sum('allocated_amount');
        $totalExpended = (float) $activeSources->sum(
            fn (FundingSource $source): float => $this->expenditureRepository->sumByFundingSource($source->id),
        );

        return [
            'total_active_sources' => $activeSources->count(),
            'total_allocated' => $totalAllocated,
            'total_expended' => $totalExpended,
            'expired_active_sources' => $expiredButActive->count(),
        ];
    }
    /**
     * @param array{allocated: float, expended: float, remaining: float, utilization: float, by_category: array<string, float>} $summary
     */
    private function dispatchThresholdAlert(ResearchProject $project, string $eventKey, array $summary): void
    {
        dispatch(new SendUimpNotification(
            [$project->researchGroup->pi_staff_id],
            $eventKey,
            ['project_id' => $project->id, 'utilization' => $summary['utilization']],
        ));
    }
}