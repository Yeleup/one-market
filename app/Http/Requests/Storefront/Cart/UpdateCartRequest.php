<?php

namespace App\Http\Requests\Storefront\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
