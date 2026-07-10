<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Repositories\AnalyticsRepository;
use Illuminate\Support\Facades\Cache;

/**
 * AnalyticsService
 *
 * Computes and caches the Research Productivity Analytics views.
 * All results are cached in Redis with a SHA-256 filter-hash key and
 * a 15-minute TTL (SDD §3.11.5). This is a read-only module — no
 * writes to any RGMS table occur here.
 *
 * SDD Reference: RGMS SDD §3.11.2, §3.11.5
 */
final class AnalyticsService
{
    private const TTL_SECONDS = 900;

    public function __construct(
        private readonly AnalyticsRepository $repository,
    ) {
    }

    /**
     * Aggregate productivity metrics: publications, patents, project
     * completion rates, and funding utilization (SDD §3.11.5 —
     * literal cache pattern).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function computeProductivity(array $filters): array
    {
        $cacheKey = 'analytics:productivity:' . hash('sha256', json_encode($filters));

        return Cache::tags(['analytics:publications', 'analytics:patents', 'analytics:projects', 'analytics:funding'])
            ->remember($cacheKey, self::TTL_SECONDS, function () use ($filters): array {
                return [
                    'publications' => $this->repository->publicationsByTypeAndYear($filters),
                    'patents' => $this->repository->patentsByStatus($filters),
                    'projects' => $this->repository->projectCompletionRates($filters),
                    'funding' => $this->repository->fundingUtilizationByGroup($filters),
                    'generated_at' => now()->toIso8601String(),
                ];
            });
    }

    /**
     * Time-series trend analysis (membership growth), grouped per
     * the requested period (monthly/quarterly/annual).
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function computeTrends(array $filters): array
    {
        $cacheKey = 'analytics:trends:' . hash('sha256', json_encode($filters));

        return Cache::tags(['analytics:trends'])
            ->remember($cacheKey, self::TTL_SECONDS, function () use ($filters): array {
                return [
                    'membership_growth' => $this->repository->membershipGrowthTrend($filters),
                    'period' => $filters['period'],
                    'generated_at' => now()->toIso8601String(),
                ];
            });
    }

    /**
     * Comparative analytics across research groups/areas —
     * currently backed by the same funding-utilization-by-group and
     * project-completion aggregations, filtered to the requested
     * scope.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function computeComparisons(array $filters): array
    {
        $cacheKey = 'analytics:comparisons:' . hash('sha256', json_encode($filters));

        return Cache::tags(['analytics:projects', 'analytics:funding'])
            ->remember($cacheKey, self::TTL_SECONDS, function () use ($filters): array {
                return [
                    'by_group_funding' => $this->repository->fundingUtilizationByGroup($filters),
                    'by_project_completion' => $this->repository->projectCompletionRates($filters),
                    'generated_at' => now()->toIso8601String(),
                ];
            });
    }

    /**
     * Single-group statistics (GET /analytics/research-groups/{gid}).
     *
     * @return array<string, mixed>
     */
    public function getGroupStats(string $groupId): array
    {
        $filters = ['group_ids' => [$groupId]];
        $cacheKey = 'analytics:group_stats:' . $groupId;

        return Cache::tags(['analytics:publications', 'analytics:patents', 'analytics:projects', 'analytics:funding'])
            ->remember($cacheKey, self::TTL_SECONDS, function () use ($filters): array {
                return [
                    'publications' => $this->repository->publicationsByTypeAndYear($filters),
                    'patents' => $this->repository->patentsByStatus($filters),
                    'projects' => $this->repository->projectCompletionRates($filters),
                    'funding' => $this->repository->fundingUtilizationByGroup($filters),
                    'generated_at' => now()->toIso8601String(),
                ];
            });
    }
}