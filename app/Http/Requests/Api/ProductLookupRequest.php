<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProductLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'query.required' => 'Please enter a product serial number, barcode, SKU, or product name.',
            'query.min' => 'Please enter at least 2 characters.',
        ];
    }
}

