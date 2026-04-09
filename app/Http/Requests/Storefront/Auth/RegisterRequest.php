<?php

namespace App\Http\Requests\Storefront\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'bin' => ['required', 'string', 'size:12', 'unique:clients,bin'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
        ];
    }
}
