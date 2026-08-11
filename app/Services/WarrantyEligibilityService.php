<?php

namespace App\Services;

use App\Enums\WarrantyStatus;
use App\Models\Product;
use App\Models\Warranty;
use App\Models\WarrantyRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class WarrantyEligibilityService
{
    /**
     * @return array{eligible: bool, result: string, requires_manual_verification: bool, duration_months: int, start_date: ?CarbonInterface, expiry_date: ?CarbonInterface}
     */
    public function evaluate(
        ?Product $product,
        ?CarbonInterface $purchaseDate,
        bool $serialFound,
        bool $hasActiveDuplicate,
        ?string $purchaseSourceCode = null,
    ): array {
        if ($hasActiveDuplicate) {
            return [
                'eligible' => false,
                'result' => 'duplicate_active_warranty',
                'requires_manual_verification' => false,
                'duration_months' => 0,
                'start_date' => null,
                'expiry_date' => null,
            ];
        }

        $rule = $this->resolveRule($product);
        $duration = $rule?->warranty_duration_months
            ?? $product?->resolvedWarrantyMonths()
            ?? (int) app(SettingsService::class)->get('default_warranty_months', 12);

        $graceDays = $rule?->registration_grace_days
            ?? $product?->registration_grace_days
            ?? (int) app(SettingsService::class)->get('registration_grace_days', 30);

        $manualAllowed = $rule?->manual_verification_allowed
            ?? $product?->manual_verification_allowed
            ?? (bool) app(SettingsService::class)->get('allow_manual_verification', true);

        if (! $serialFound) {
            return [
                'eligible' => $manualAllowed,
                'result' => $manualAllowed ? 'serial_not_found_manual_verification' : 'serial_not_found',
                'requires_manual_verification' => $manualAllowed,
                'duration_months' => $duration,
                'start_date' => null,
                'expiry_date' => null,
            ];
        }

        if ($purchaseDate && $purchaseDate->copy()->startOfDay()->diffInDays(now()->startOfDay()) > $graceDays) {
            return [
                'eligible' => $manualAllowed,
                'result' => 'outside_registration_grace_period',
                'requires_manual_verification' => $manualAllowed,
                'duration_months' => $duration,
                'start_date' => null,
                'expiry_date' => null,
            ];
        }

        if ($rule && is_array($rule->eligible_purchase_sources) && $purchaseSourceCode) {
            if (! in_array($purchaseSourceCode, $rule->eligible_purchase_sources, true)) {
                return [
                    'eligible' => false,
                    'result' => 'purchase_source_not_eligible',
                    'requires_manual_verification' => false,
                    'duration_months' => $duration,
                    'start_date' => null,
                    'expiry_date' => null,
                ];
            }
        }

        [$startDate, $expiryDate] = $this->resolvePeriodFromPurchaseDate($purchaseDate, $duration);

        return [
            'eligible' => true,
            'result' => 'eligible',
            'requires_manual_verification' => false,
            'duration_months' => $duration,
            'start_date' => $startDate,
            'expiry_date' => $expiryDate,
            'start_date_method' => 'purchase_date',
        ];
    }

    /**
     * Warranty coverage always begins on the purchase date, not the registration date.
     *
     * @return array{0: ?CarbonInterface, 1: ?CarbonInterface}
     */
    public function resolvePeriodFromPurchaseDate(?CarbonInterface $purchaseDate, int $durationMonths): array
    {
        if (! $purchaseDate) {
            return [null, null];
        }

        $startDate = Carbon::parse($purchaseDate)->startOfDay();
        $expiryDate = $startDate->copy()->addMonthsNoOverflow($durationMonths)->startOfDay();

        return [$startDate, $expiryDate];
    }

    public function findActiveBySerial(string $serialNumber): ?Warranty
    {
        return Warranty::query()
            ->where('serial_number', $serialNumber)
            ->where('status', WarrantyStatus::Active)
            ->with(['customer', 'product', 'purchaseSource'])
            ->first();
    }

    protected function resolveRule(?Product $product): ?WarrantyRule
    {
        if (! $product) {
            return WarrantyRule::query()->whereNull('product_id')->whereNull('product_category_id')->where('is_active', true)->first();
        }

        return WarrantyRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere(function ($q) use ($product) {
                        $q->whereNull('product_id')->where('product_category_id', $product->product_category_id);
                    })
                    ->orWhere(function ($q) {
                        $q->whereNull('product_id')->whereNull('product_category_id');
                    });
            })
            ->orderByRaw('CASE WHEN product_id IS NOT NULL THEN 0 WHEN product_category_id IS NOT NULL THEN 1 ELSE 2 END')
            ->first();
    }
}
