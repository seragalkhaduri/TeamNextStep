<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ComparisonResource
 *
 * Wraps the structured array returned by
 * AnalyticsService::computeComparisons().
 *
 * @property-read array<string, mixed> $resource
 *
 * SDD Reference: RGMS SDD §3.11.3, §3.11.5
 */
final class ComparisonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'by_group_funding' => $this->resource['by_group_funding'],
            'by_project_completion' => $this->resource['by_project_completion'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}