<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\WarrantyStatus;
use App\Models\Warranty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WarrantyQueryService
{
    public function __construct(protected PhoneNumberService $phoneNumberService) {}

    public function lookup(?string $serialNumber, ?string $mobileNumber): ?Warranty
    {
        $normalizedMobile = $this->phoneNumberService->normalize($mobileNumber);
        $serial = strtoupper(trim((string) $serialNumber));

        if (! $normalizedMobile || $serial === '') {
            return null;
        }

        return Warranty::query()
            ->with(['customer', 'product', 'purchaseSource', 'dealer'])
            ->where('serial_number', $serial)
            ->whereHas('customer', fn ($q) => $q->where('mobile_normalized', $normalizedMobile))
            ->latest()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminSearch(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Warranty::query()
            ->with(['customer', 'product', 'purchaseSource', 'dealer'])
            ->withCount([
                'notificationLogs as email_notifications_count' => fn ($q) => $q
                    ->where('status', 'sent')
                    ->where('channel', NotificationChannel::Email),
                'notificationLogs as sms_notifications_count' => fn ($q) => $q
                    ->where('status', 'sent')
                    ->where('channel', NotificationChannel::Sms),
                'notificationLogs as notifications_sent_count' => fn ($q) => $q
                    ->where('status', 'sent'),
            ])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['product_category_id'])) {
            $query->where('product_category_id', $filters['product_category_id']);
        }

        if (! empty($filters['purchase_source_id'])) {
            $query->where('purchase_source_id', $filters['purchase_source_id']);
        }

        if (! empty($filters['dealer_id'])) {
            $query->where('dealer_id', $filters['dealer_id']);
        }

        if (! empty($filters['registration_source'])) {
            $query->where('registration_source', $filters['registration_source']);
        }

        if (! empty($filters['marketing_consent'])) {
            $query->where('marketing_consent', $filters['marketing_consent'] === '1');
        }

        if (! empty($filters['odoo_validated'])) {
            $query->where('odoo_validated', $filters['odoo_validated'] === '1');
        }

        if (! empty($filters['registered_from'])) {
            $query->whereDate('registration_date', '>=', $filters['registered_from']);
        }

        if (! empty($filters['registered_to'])) {
            $query->whereDate('registration_date', '<=', $filters['registered_to']);
        }

        if (! empty($filters['purchase_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['purchase_from']);
        }

        if (! empty($filters['purchase_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['purchase_to']);
        }

        if (! empty($filters['expiry_from'])) {
            $query->whereDate('warranty_expiry_date', '>=', $filters['expiry_from']);
        }

        if (! empty($filters['expiry_to'])) {
            $query->whereDate('warranty_expiry_date', '<=', $filters['expiry_to']);
        }

        if (! empty($filters['q'])) {
            $term = $filters['q'];
            $normalized = $this->phoneNumberService->normalize($term);
            $query->where(function ($q) use ($term, $normalized) {
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhere('serial_number', 'like', "%{$term}%")
                    ->orWhere('invoice_number', 'like', "%{$term}%")
                    ->orWhere('odoo_pos_order_id', 'like', "%{$term}%")
                    ->orWhere('odoo_sales_order_id', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($term, $normalized) {
                        $customerQuery->where('full_name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('mobile_number', 'like', "%{$term}%");
                        if ($normalized) {
                            $customerQuery->orWhere('mobile_normalized', $normalized);
                        }
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function pendingVerification()
    {
        return Warranty::query()
            ->with(['customer', 'product'])
            ->whereIn('status', [
                WarrantyStatus::PendingVerification->value,
                WarrantyStatus::UnderReview->value,
            ])
            ->latest()
            ->paginate(20);
    }
}
