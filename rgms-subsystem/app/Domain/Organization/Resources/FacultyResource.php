<?php

namespace App\Domain\Organization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacultyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nameEn' => $this->name_en,
            'nameAr' => $this->name_ar,
            'name' => $this->getLocalizedName($request),
            'code' => $this->code,
            'departmentCount' => $this->whenCounted('departments'),
            'departments' => DepartmentResource::collection($this->whenLoaded('departments')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function getLocalizedName(Request $request): string
    {
        $lang = $request->header('Accept-Language', 'ar');
        return str_starts_with($lang, 'en') ? $this->name_en : $this->name_ar;
    }
}
