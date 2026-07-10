<?php

namespace App\Domain\Facilities\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'roomType' => $this->room_type?->value,
            'capacity' => $this->capacity,
            'availabilityStatus' => $this->availability_status?->value,
            'buildingId' => $this->building_id,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
