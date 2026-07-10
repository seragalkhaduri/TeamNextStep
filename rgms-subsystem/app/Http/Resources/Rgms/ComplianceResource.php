<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ComplianceResource
 *
 * @property-read \App\Models\ComplianceRecord $resource
 *
 * SDD Reference: RGMS SDD §3.10.3, §4.2.12
 */
final class ComplianceResource extends JsonResource
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
            'project_id' => $this->project_id,
            'condition_type' => $this->condition_type,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'regulatory_reference' => $this->regulatory_reference,
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolved_by' => $this->resolved_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}