<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'mobile_number' => ['required', 'string', 'max:30'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $normalized = app(PhoneNumberService::class)->normalize($this->input('mobile_number'));
            if (! $normalized || strlen($normalized) < 12) {
                $validator->errors()->add('mobile_number', 'Enter a valid Kenyan mobile number for OTP (e.g. 07XXXXXXXX or +2547XXXXXXXX).');
            }
        });
    }

    /**
     * @return array{name: string, email: string, mobile_number: string, mobile_normalized: string}
     */
    public function profileData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'],
            'mobile_normalized' => app(PhoneNumberService::class)->normalize($data['mobile_number']),
        ];
    }
}
