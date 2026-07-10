<?php

namespace App\Domain\Employees\Requests;

use App\Domain\Employees\Enums\AcademicRank;
use App\Domain\Employees\Enums\StaffType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            'SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'HR_STAFF',
        ]);
    }

    public function rules(): array
    {
        return [
            'institutionalId' => ['required', 'string', 'max:50'],
            'staffType' => ['required', 'string', Rule::enum(StaffType::class)],
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'academicRank' => ['nullable', 'string', Rule::enum(AcademicRank::class)],
            'hireDate' => ['required', 'date'],
            'departmentIds' => ['nullable', 'array'],
            'departmentIds.*' => ['uuid', 'exists:departments,id'],
        ];
    }
}
