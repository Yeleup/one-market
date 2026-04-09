<?php

namespace App\Http\Requests\Storefront\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'bin' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
