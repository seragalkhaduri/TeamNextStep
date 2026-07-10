<?php

namespace App\Domain\Students\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * StudentResource — exact response shape from SDD §7:
 *
 * {
 *   id, institutionalId, name (localized), nameEn,
 *   enrollmentStatus, admissionDate,
 *   programs: [{ programId, programName }],
 *   contactInfo: { email, phone }
 * }
 */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->header('Accept-Language', 'ar');
        $localizedName = str_starts_with($lang, 'en') ? $this->name_en : $this->name_ar;

        return [
            'id' => $this->id,
            'institutionalId' => $this->institutional_id,
            'nationalId' => $this->national_id,
            'name' => $localizedName,
            'nameEn' => $this->name_en,
            'nameAr' => $this->name_ar,
            'dateOfBirth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender?->value,
            'nationality' => $this->nationality,
            'enrollmentStatus' => $this->enrollment_status?->value,
            'admissionDate' => $this->admission_date?->toDateString(),
            'graduationDate' => $this->graduation_date?->toDateString(),
            'programs' => $this->whenLoaded('programs', function () use ($lang) {
                return $this->programs->map(fn ($p) => [
                    'programId' => $p->id,
                    'programName' => str_starts_with($lang, 'en') ? $p->name_en : $p->name_ar,
                    'enrollmentDate' => $p->pivot->enrollment_date,
                ]);
            }),
            'contactInfo' => [
                'email' => $this->email,
                'phone' => $this->phone,
            ],
            'address' => $this->address,
            'userId' => $this->user_id,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
