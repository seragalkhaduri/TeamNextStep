<?php

namespace App\Domain\Employees\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * EmployeeResource — camelCase API shape per SDD §7.
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language', 'ar');
        $localizedName = str_starts_with($lang, 'en') ? $this->name_en : $this->name_ar;

        return [
            'id' => $this->id,
            'institutionalId' => $this->institutional_id,
            'staffType' => $this->staff_type?->value,
            'name' => $localizedName,
            'nameEn' => $this->name_en,
            'nameAr' => $this->name_ar,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'academicRank' => $this->academic_rank?->value,
            'hireDate' => $this->hire_date?->toDateString(),
            'status' => $this->status,
            'departments' => $this->whenLoaded('departments', function () use ($lang) {
                return $this->departments->map(fn ($d) => [
                    'departmentId' => $d->id,
                    'departmentName' => str_starts_with($lang, 'en') ? $d->name_en : $d->name_ar,
                    'assignedAt' => $d->pivot->assigned_at,
                ]);
            }),
            'userId' => $this->user_id,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
