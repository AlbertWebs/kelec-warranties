<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class WarrantyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial_number' => ['required', 'string', 'min:4', 'max:100'],
            'full_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'county' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'purchase_source_id' => ['required', 'exists:purchase_sources,id'],
            'dealer_id' => ['nullable', 'exists:dealers,id'],
            'branch_name' => ['nullable', 'string', 'max:150'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'product_name' => ['nullable', 'string', 'max:150'],
            'product_model' => ['nullable', 'string', 'max:150'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'privacy_accepted' => ['accepted'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'marketing_consent' => $this->boolean('marketing_consent'),
        ]);
    }

    public function messages(): array
    {
        return [
            'privacy_accepted.accepted' => 'You must accept the Privacy Policy and warranty terms to continue.',
        ];
    }
}
