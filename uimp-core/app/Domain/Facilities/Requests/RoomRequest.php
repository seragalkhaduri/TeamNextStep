<?php

namespace App\Domain\Facilities\Requests;

use App\Domain\Facilities\Enums\AvailabilityStatus;
use App\Domain\Facilities\Enums\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN', 'DEPARTMENT_ADMIN']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'roomType' => ['required', 'string', Rule::enum(RoomType::class)],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'availabilityStatus' => ['required', 'string', Rule::enum(AvailabilityStatus::class)],
            'buildingId' => ['required', 'uuid', 'exists:buildings,id'],
        ];
    }
}
