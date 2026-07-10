<?php

namespace App\Domain\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'DEPARTMENT_ADMIN']);
    }

    public function rules(): array
    {
        $id = $this->route('department');
        return [
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,' . $id . ',id'],
            'facultyId' => ['required', 'uuid', 'exists:faculties,id'],
            'parentDepartmentId' => ['nullable', 'uuid', 'exists:departments,id'],
        ];
    }
}
