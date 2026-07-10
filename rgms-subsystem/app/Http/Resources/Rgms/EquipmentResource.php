<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * EquipmentResource
 *
 * @property-read \App\Models\ResearchEquipment $resource
 *
 * SDD Reference: RGMS SDD §3.8.3
 */
final class EquipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lastMaintenance = $this->relationLoaded('equipmentMaintenance')
            ? $this->equipmentMaintenance->whereNotNull('completion_date')->sortByDesc('completion_date')->first()
            : $this->equipmentMaintenance()->whereNotNull('completion_date')->orderByDesc('completion_date')->first();

        return [
            'id' => $this->id,
            'research_group_id' => $this->research_group_id,
            'asset_name' => $this->asset_name,
            'category' => $this->category,
            'manufacturer' => $this->manufacturer,
            'model_number' => $this->model_number,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'acquisition_cost' => $this->acquisition_cost,
            'replacement_value' => $this->replacement_value,
            'estimated_useful_life_years' => $this->estimated_useful_life_years,
            'age_years' => $this->purchase_date?->diffInYears(now()),
            'laboratory_ref_id' => $this->laboratory_ref_id,
            'status' => $this->status,
            'last_maintenance_date' => $lastMaintenance?->completion_date?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}