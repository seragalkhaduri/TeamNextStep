<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ComplianceDashboardResource
 *
 * Wraps the structured array returned by
 * ComplianceService::getDashboardSummary() (FR-AUD-011).
 *
 * @property-read array{by_status: array<string, int>, non_compliant: list<array<string, mixed>>, approaching_due: list<array<string, mixed>>} $resource
 *
 * SDD Reference: RGMS SDD §3.10.3, §3.10.5
 */
final class ComplianceDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'by_status' => $this->resource['by_status'],
            'non_compliant' => $this->resource['non_compliant'],
            'approaching_due' => $this->resource['approaching_due'],
        ];
    }
}