<?php

namespace App\Domain\Students\Requests;

use App\Domain\Students\Enums\EnrollmentStatus;
use App\Domain\Students\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CreateStudentRequest — validates student creation per SDD §7.
 * Both nameEn and nameAr are required (DB-004 dual-language pattern).
 */
class CreateStudentRequest extends FormRequest
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
            'institutionalId' => ['required', 'string', 'max:50'],
            'nationalId' => ['required', 'string', 'max:50'],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', Rule::enum(Gender::class)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'admissionDate' => ['required', 'date'],
            'programIds' => ['nullable', 'array'],
            'programIds.*' => ['uuid', 'exists:programs,id'],
        ];
    }
}
