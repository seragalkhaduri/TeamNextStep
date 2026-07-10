<?php

namespace App\Domain\Facilities\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuildingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language', 'ar');

        return [
            'id' => $this->id,
            'nameEn' => $this->name_en,
            'nameAr' => $this->name_ar,
            'name' => str_starts_with($lang, 'en') ? $this->name_en : $this->name_ar,
            'code' => $this->code,
            'campusId' => $this->campus_id,
            'campus' => new CampusResource($this->whenLoaded('campus')),
            'roomCount' => $this->whenCounted('rooms'),
            'rooms' => RoomResource::collection($this->whenLoaded('rooms')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
