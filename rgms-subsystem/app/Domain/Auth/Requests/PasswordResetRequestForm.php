<?php

namespace App\Domain\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PasswordResetRequestForm — validates password reset request (email).
 */
class PasswordResetRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
