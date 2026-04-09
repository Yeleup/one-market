<?php

namespace App\Http\Requests\Storefront;

use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'language' => [
                'required',
                'string',
                Rule::exists((new Language)->getTable(), 'code')
                    ->where('is_active', true),
            ],
        ];
    }
}
