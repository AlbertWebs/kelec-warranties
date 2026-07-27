<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function __construct(protected PhoneNumberService $phoneNumberService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function findOrCreate(array $data): Customer
    {
        $normalized = $this->phoneNumberService->normalize($data['mobile_number']);

        $customer = null;

        if ($normalized) {
            $customer = Customer::query()->where('mobile_normalized', $normalized)->first();
        }

        if (! $customer && ! empty($data['odoo_customer_id'])) {
            $customer = Customer::query()->where('odoo_customer_id', $data['odoo_customer_id'])->first();
        }

        if (! $customer && ! empty($data['email'])) {
            $customer = Customer::query()->where('email', $data['email'])->first();
        }

        if ($customer) {
            $customer->update([
                'full_name' => $data['full_name'] ?: $customer->full_name,
                'mobile_number' => $data['mobile_number'] ?: $customer->mobile_number,
                'mobile_normalized' => $normalized ?: $customer->mobile_normalized,
                'email' => $data['email'] ?? $customer->email,
                'county' => $data['county'] ?? $customer->county,
                'town' => $data['town'] ?? $customer->town,
                'odoo_customer_id' => $data['odoo_customer_id'] ?? $customer->odoo_customer_id,
                'marketing_consent' => array_key_exists('marketing_consent', $data)
                    ? (bool) $data['marketing_consent']
                    : $customer->marketing_consent,
                'marketing_consent_at' => array_key_exists('marketing_consent', $data) && $data['marketing_consent']
                    ? now()
                    : $customer->marketing_consent_at,
            ]);

            return $customer->fresh();
        }

        $similar = Customer::query()
            ->where(function ($q) use ($normalized, $data) {
                if ($normalized) {
                    $q->where('mobile_normalized', $normalized);
                }
                if (! empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            })
            ->exists();

        return Customer::create([
            'full_name' => $data['full_name'],
            'mobile_number' => $data['mobile_number'],
            'mobile_normalized' => $normalized,
            'email' => $data['email'] ?? null,
            'county' => $data['county'] ?? null,
            'town' => $data['town'] ?? null,
            'odoo_customer_id' => $data['odoo_customer_id'] ?? null,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'marketing_consent_at' => ! empty($data['marketing_consent']) ? now() : null,
            'possible_duplicate' => $similar,
        ]);
    }
}
