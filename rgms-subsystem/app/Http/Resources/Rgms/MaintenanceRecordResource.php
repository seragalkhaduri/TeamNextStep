<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MaintenanceRecordResource
 *
 * @property-read \App\Models\EquipmentMaintenance $resource
 *
 * SDD Reference: RGMS SDD §3.8.4, §4.2.14
 */
final class MaintenanceRecordResource extends JsonResource
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
            'equipment_id' => $this->equipment_id,
            'maintenance_type' => $this->maintenance_type,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'performed_by' => $this->performed_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}