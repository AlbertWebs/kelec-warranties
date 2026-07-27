<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class WarrantyLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'max:20'],
            'reference' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('reference') && ! $this->filled('serial_number')) {
                $validator->errors()->add('reference', 'Provide a warranty reference or serial number.');
            }
        });
    }
}
