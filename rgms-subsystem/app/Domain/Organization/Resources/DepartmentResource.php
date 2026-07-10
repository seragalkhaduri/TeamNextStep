<?php

namespace App\Domain\Organization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'facultyId' => $this->faculty_id,
            'faculty' => new FacultyResource($this->whenLoaded('faculty')),
            'parentDepartmentId' => $this->parent_department_id,
            'parentDepartment' => new DepartmentResource($this->whenLoaded('parentDepartment')),
            'childDepartments' => DepartmentResource::collection($this->whenLoaded('childDepartments')),
            'programs' => ProgramResource::collection($this->whenLoaded('programs')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
