<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BudgetSummaryResource
 *
 * Wraps the structured array returned by
 * FundingService::computeBudgetSummary(): allocated, expended,
 * remaining, utilization, and a per-category breakdown (FR-FUND-004).
 *
 * @property-read array{allocated: float, expended: float, remaining: float, utilization: float, by_category: array<string, float>} $resource
 *
 * SDD Reference: RGMS SDD §3.5.3, §3.5.5
 */
final class BudgetSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_allocated' => $this->resource['allocated'],
            'total_expended' => $this->resource['expended'],
            'remaining_balance' => $this->resource['remaining'],
            'utilization_percentage' => round($this->resource['utilization'] * 100, 2),
            'by_category' => $this->resource['by_category'],
        ];
    }
}