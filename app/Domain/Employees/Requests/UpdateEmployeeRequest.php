<?php

namespace App\Domain\Employees\Requests;

use App\Domain\Employees\Enums\AcademicRank;
use App\Domain\Employees\Enums\StaffType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
            'institutionalId' => ['sometimes', 'string', 'max:50'],
            'staffType' => ['sometimes', 'string', Rule::enum(StaffType::class)],
            'nameEn' => ['sometimes', 'string', 'max:255'],
            'nameAr' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'academicRank' => ['nullable', 'string', Rule::enum(AcademicRank::class)],
            'hireDate' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'departmentIds' => ['nullable', 'array'],
            'departmentIds.*' => ['uuid', 'exists:departments,id'],
        ];
    }
}
