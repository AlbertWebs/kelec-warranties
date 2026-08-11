<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;

class WarrantyDurationResolver
{
    public function __construct(
        protected SettingsService $settings,
        protected WarrantyEligibilityService $eligibilityService,
    ) {}

    public function defaultMonths(): int
    {
        return max(1, (int) $this->settings->get('default_warranty_months', 12));
    }

    public function forCategory(?ProductCategory $category): int
    {
        if ($category?->default_warranty_months) {
            return (int) $category->default_warranty_months;
        }

        return $this->defaultMonths();
    }

    public function forProduct(?Product $product): int
    {
        if ($product?->default_warranty_months) {
            return (int) $product->default_warranty_months;
        }

        return $this->forCategory($product?->category);
    }

    public function forProductWithRule(?Product $product): int
    {
        if ($product?->default_warranty_months) {
            return (int) $product->default_warranty_months;
        }

        $rule = $this->eligibilityService->resolveRule($product);

        if ($rule?->warranty_duration_months) {
            return (int) $rule->warranty_duration_months;
        }

        return $this->forCategory($product?->category);
    }
}
