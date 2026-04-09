<?php

namespace App\Http\Requests\Storefront\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
        ];
    }
}
