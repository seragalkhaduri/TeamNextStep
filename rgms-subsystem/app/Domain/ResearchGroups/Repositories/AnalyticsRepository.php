<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * AnalyticsRepository
 *
 * Read-only aggregation repository. Uses raw DB::select() with
 * parameterized queries for complex multi-table aggregations not
 * expressible cleanly in Eloquent. No writes ever occur here (SDD
 * §3.11.6). Since these are raw queries (not Eloquent models), each
 * explicitly includes WHERE deleted_at IS NULL rather than relying on
 * Eloquent's automatic SoftDeletingScope (SDD §3.11.8).
 *
 * SDD Reference: RGMS SDD §3.11.6, §3.11.8
 */
final class AnalyticsRepository
{
    /**
     * Publication counts grouped by publication_type and year.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function publicationsByTypeAndYear(array $filters): array
    {
        $bindings = $this->dateRangeBindings($filters, 'created_at');

        return DB::select(
            'SELECT publication_type, publication_year AS year, COUNT(*) AS total
             FROM publications
             WHERE deleted_at IS NULL
               AND (:from_date IS NULL OR created_at >= :from_date)
               AND (:to_date IS NULL OR created_at <= :to_date)
             GROUP BY publication_type, publication_year
             ORDER BY publication_year DESC',
            $bindings,
        );
    }

    /**
     * Patent counts grouped by status.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function patentsByStatus(array $filters): array
    {
        $bindings = $this->dateRangeBindings($filters, 'created_at');

        return DB::select(
            'SELECT status, COUNT(*) AS total
             FROM patents
             WHERE deleted_at IS NULL
               AND (:from_date IS NULL OR created_at >= :from_date)
               AND (:to_date IS NULL OR created_at <= :to_date)
             GROUP BY status',
            $bindings,
        );
    }

    /**
     * Milestone completion rate (completed_milestones / total_milestones)
     * per project.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function projectCompletionRates(array $filters): array
    {
        $bindings = $this->dateRangeBindings($filters, 'rp.created_at');

        return DB::select(
            "SELECT rp.id AS project_id, rp.title,
                    COUNT(pm.id) AS total_milestones,
                    SUM(CASE WHEN pm.status = 'Completed' THEN 1 ELSE 0 END) AS completed_milestones
             FROM research_projects rp
             LEFT JOIN project_milestones pm ON pm.project_id = rp.id AND pm.deleted_at IS NULL
             WHERE rp.deleted_at IS NULL
               AND (:from_date IS NULL OR rp.created_at >= :from_date)
               AND (:to_date IS NULL OR rp.created_at <= :to_date)
             GROUP BY rp.id, rp.title",
            $bindings,
        );
    }

    /**
     * Funding utilization (SUM(expenditures) / SUM(allocations)) per
     * research group.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function fundingUtilizationByGroup(array $filters): array
    {
        return DB::select(
            'SELECT rg.id AS group_id, rg.group_name,
                    COALESCE(SUM(fs.allocated_amount), 0) AS total_allocated,
                    COALESCE((
                        SELECT SUM(be.amount)
                        FROM budget_expenditures be
                        INNER JOIN research_projects rp ON rp.id = be.project_id
                        WHERE rp.research_group_id = rg.id
                    ), 0) AS total_expended
             FROM research_groups rg
             LEFT JOIN funding_sources fs ON fs.research_group_id = rg.id AND fs.deleted_at IS NULL
             WHERE rg.deleted_at IS NULL
             GROUP BY rg.id, rg.group_name',
        );
    }

    /**
     * New membership counts per period (month) — used for trend
     * analysis.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function membershipGrowthTrend(array $filters): array
    {
        $bindings = $this->dateRangeBindings($filters, 'created_at');

        return DB::select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, COUNT(*) AS total
             FROM group_memberships
             WHERE deleted_at IS NULL
               AND (:from_date IS NULL OR created_at >= :from_date)
               AND (:to_date IS NULL OR created_at <= :to_date)
             GROUP BY period
             ORDER BY period",
            $bindings,
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{from_date: string|null, to_date: string|null}
     */
    private function dateRangeBindings(array $filters, string $column): array
    {
        return [
            'from_date' => $filters['from_date'] ?? null,
            'to_date' => $filters['to_date'] ?? null,
        ];
    }
}