<?php

namespace App\Domain\Students\Requests;

use App\Domain\Students\Enums\EnrollmentStatus;
use App\Domain\Students\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateStudentRequest — validates student update.
 */
class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            'SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'REGISTRAR_STAFF',
        ]);
    }

    public function rules(): array
    {
        return [
            'institutionalId' => ['sometimes', 'string', 'max:50'],
            'nationalId' => ['sometimes', 'string', 'max:50'],
            'nameEn' => ['sometimes', 'string', 'max:255'],
            'nameAr' => ['sometimes', 'string', 'max:255'],
            'dateOfBirth' => ['sometimes', 'date', 'before:today'],
            'gender' => ['sometimes', 'string', Rule::enum(Gender::class)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'enrollmentStatus' => ['sometimes', 'string', Rule::enum(EnrollmentStatus::class)],
            'admissionDate' => ['sometimes', 'date'],
            'graduationDate' => ['nullable', 'date'],
            'programIds' => ['nullable', 'array'],
            'programIds.*' => ['uuid', 'exists:programs,id'],
        ];
    }
}
