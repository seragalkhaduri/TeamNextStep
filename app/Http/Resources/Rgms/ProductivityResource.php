<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductivityResource
 *
 * Wraps the structured array returned by
 * AnalyticsService::computeProductivity().
 *
 * @property-read array<string, mixed> $resource
 *
 * SDD Reference: RGMS SDD §3.11.3, §3.11.5
 */
final class ProductivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'publications' => $this->resource['publications'],
            'patents' => $this->resource['patents'],
            'projects' => $this->resource['projects'],
            'funding' => $this->resource['funding'],
            'generated_at' => $this->resource['generated_at'],
        ];
    }
}