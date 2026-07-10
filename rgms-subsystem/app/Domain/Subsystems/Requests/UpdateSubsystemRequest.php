<?php

namespace App\Domain\Subsystems\Requests;

use App\Domain\Subsystems\Enums\SubsystemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubsystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN']);
    }

    public function rules(): array
    {
        return [
            'nameEn' => ['sometimes', 'string', 'max:255'],
            'nameAr' => ['sometimes', 'string', 'max:255'],
            'descriptionEn' => ['nullable', 'string', 'max:1000'],
            'descriptionAr' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', Rule::enum(SubsystemStatus::class)],
            'webhookUrl' => ['nullable', 'url', 'max:255'],
            'contactEmail' => ['sometimes', 'email', 'max:255'],
        ];
    }
}
