<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:client'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
