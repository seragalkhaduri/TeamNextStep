<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ReportScheduleResource
 *
 * @property-read \App\Models\ReportSchedule $resource
 *
 * SDD Reference: RGMS SDD §3.12.3
 */
final class ReportScheduleResource extends JsonResource
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
            'report_type' => $this->report_type,
            'format' => $this->format,
            'frequency' => $this->frequency,
            'scope_config' => $this->scope_config,
            'recipient_config' => $this->recipient_config,
            'is_active' => $this->is_active,
            'next_run_at' => $this->next_run_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}