<?php

namespace App\Domain\Facilities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN']);
    }

    public function rules(): array
    {
        $id = $this->route('building');
        return [
            'nameEn' => ['required', 'string', 'max:255'],
            'nameAr' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:buildings,code,' . $id . ',id'],
            'campusId' => ['required', 'uuid', 'exists:campuses,id'],
        ];
    }
}
