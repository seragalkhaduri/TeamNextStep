<?php

namespace App\Domain\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LoginRequest — validates login credentials (SDD §6.2).
 * All inputs validated; never raw $request->all() into a query.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
        ];
    }
}
