<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TrendResource
 *
 * Wraps the structured array returned by
 * AnalyticsService::computeTrends().
 *
 * @property-read array<string, mixed> $resource
 *
 * SDD Reference: RGMS SDD §3.11.3, §3.11.5
 */
final class TrendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'membership_growth' => $this->resource['membership_growth'],
            'period' => $this->resource['period'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}