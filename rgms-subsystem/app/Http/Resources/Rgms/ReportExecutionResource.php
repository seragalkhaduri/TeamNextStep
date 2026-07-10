<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ReportExecutionResource
 *
 * @property-read \App\Models\ReportExecutionHistory $resource
 *
 * SDD Reference: RGMS SDD §3.12.3
 */
final class ReportExecutionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'report_type' => $this->report_type,
            'format' => $this->format,
            'status' => $this->status,
            'file_size' => $this->file_size,
            'generated_by' => $this->generated_by,
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}