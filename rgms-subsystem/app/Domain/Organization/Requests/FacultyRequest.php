<?php

namespace App\Domain\Organization\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN']);
    }

    public function rules(): array
    {
        $id = $this->route('faculty');
        return [
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:faculties,code,' . $id . ',id'],
        ];
    }
}
