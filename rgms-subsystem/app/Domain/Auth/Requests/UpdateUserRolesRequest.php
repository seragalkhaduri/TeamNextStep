<?php

namespace App\Domain\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateUserRolesRequest — validates role assignment (SDD §7, admin-only).
 */
class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['SYSTEM_ADMIN', 'UNIVERSITY_ADMIN']);
    }

    public function rules(): array
    {
        return [
            'roleIds' => ['required', 'array', 'min:1'],
            'roleIds.*' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
