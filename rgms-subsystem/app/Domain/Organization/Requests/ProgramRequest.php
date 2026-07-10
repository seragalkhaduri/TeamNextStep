<?php

namespace App\Domain\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'DEPARTMENT_ADMIN']);
    }

    public function rules(): array
    {
        return [
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'degreeLevel' => ['required', 'string', 'max:50'],
            'departmentId' => ['required', 'uuid', 'exists:departments,id'],
        ];
    }
}
