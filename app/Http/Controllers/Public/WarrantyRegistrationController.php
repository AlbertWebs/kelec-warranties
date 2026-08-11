<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SerialCheckRequest;
use App\Http\Requests\Public\WarrantyRegistrationRequest;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseSource;
use App\Services\WarrantyRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyRegistrationController extends Controller
{
    public function __construct(
        protected WarrantyRegistrationService $registrationService,
        protected \App\Services\SettingsService $settingsService,
    ) {}

    public function create(Request $request): View
    {
        $brandShopSource = PurchaseSource::query()->where('code', 'brand_shop')->first();
        $dealerSource = PurchaseSource::query()->where('code', 'dealer')->first();

        return view('public.register.index', [
            'purchaseSources' => PurchaseSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'dealers' => Dealer::query()->where('is_active', true)->orderBy('name')->get(),
            'brandShops' => $this->brandShopOptions(),
            'brandShopSourceId' => $brandShopSource?->id,
            'dealerSourceId' => $dealerSource?->id,
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'prefill' => $request->session()->get('registration_prefill', []),
            'serialResult' => $request->session()->get('serial_result'),
        ]);
    }

    public function checkSerial(SerialCheckRequest $request): RedirectResponse|JsonResponse
    {
        $serial = strtoupper(trim($request->validated('serial_number')));
        $result = $this->registrationService->checkSerial($serial);

        if (($result['status'] ?? null) === 'existing_active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'status' => 'existing_active',
                    'message' => $result['message'],
                    'warranty_reference' => $result['warranty']->reference ?? null,
                    'redirect_url' => route('warranty.lookup', ['serial' => $serial]),
                ], 409);
            }

            return redirect()
                ->route('warranty.lookup', ['serial' => $serial])
                ->with('warning', $result['message'])
                ->with('lookup_serial', $serial);
        }

        $sale = is_array($result['odoo']['sale'] ?? null) ? $result['odoo']['sale'] : [];
        $inStock = ($sale['sale_status'] ?? null) === 'in_stock';
        $place = $this->resolvePurchasePlace($sale);

        $prefill = [
            'serial_number' => $serial,
            'product_id' => $result['odoo']['product']['id'] ?? null,
            'product_name' => $result['odoo']['product']['name'] ?? null,
            'product_model' => $result['odoo']['product']['model'] ?? null,
            'product_category_id' => $result['odoo']['product']['category_id'] ?? null,
            // Internal transfers must not invent a purchase date / invoice.
            'purchase_date' => $inStock ? null : ($sale['purchase_date'] ?? null),
            'invoice_number' => $inStock ? null : ($sale['invoice_number'] ?? null),
            'branch_name' => $place['branch_name'],
            'purchase_source_id' => $place['purchase_source_id'],
            'dealer_id' => $place['dealer_id'],
            'purchase_place_label' => $place['purchase_place_label'],
            'sale_status' => $sale['sale_status'] ?? null,
            'current_location' => $sale['current_location'] ?? null,
            'full_name' => $inStock ? null : ($result['odoo']['customer']['full_name'] ?? null),
            'mobile_number' => $inStock ? null : ($result['odoo']['customer']['mobile_number'] ?? null),
            'email' => $inStock ? null : ($result['odoo']['customer']['email'] ?? null),
            'county' => $inStock ? null : ($result['odoo']['customer']['county'] ?? null),
            'town' => $inStock ? null : ($result['odoo']['customer']['town'] ?? null),
        ];

        $prefill['product_id'] = $this->resolveLocalProductIdFromPrefill($prefill, $result);

        if ($request->expectsJson()) {
            $status = (string) ($result['status'] ?? 'not_found');
            $validated = in_array($status, ['found', 'found_local'], true);

            return response()->json([
                'success' => true,
                'status' => $status,
                'validated' => $validated,
                'sale_status' => $prefill['sale_status'],
                'message' => $result['message'] ?? 'Serial check completed.',
                'prefill' => $prefill,
                'product' => [
                    'name' => $prefill['product_name'],
                    'model' => $prefill['product_model'],
                    'purchase_date' => $prefill['purchase_date'],
                    'invoice_number' => $prefill['invoice_number'],
                    'branch_name' => $prefill['branch_name'],
                    'purchase_place' => $prefill['purchase_place_label'],
                    'purchase_source_id' => $prefill['purchase_source_id'],
                    'dealer_id' => $prefill['dealer_id'],
                    'sale_status' => $prefill['sale_status'],
                    'current_location' => $prefill['current_location'],
                ],
            ]);
        }

        return redirect()
            ->route('register-warranty.create')
            ->with('serial_result', $result)
            ->with('registration_prefill', $prefill);
    }

    public function store(WarrantyRegistrationRequest $request): RedirectResponse|JsonResponse
    {
        $warranty = $this->registrationService->register(
            $request->validated(),
            $request->file('receipt')
        );

        $request->session()->forget(['registration_prefill', 'serial_result']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thank you. Your warranty registration has been submitted successfully.',
                'reference' => $warranty->reference,
                'next_url' => route('register-warranty.success', $warranty->reference),
                'lookup_url' => route('warranty.lookup', ['serial' => $warranty->serial_number]),
                'certificate_url' => route('warranty.certificate', $warranty->reference),
            ]);
        }

        return redirect()
            ->route('register-warranty.success', $warranty->reference);
    }

    public function success(string $reference): View
    {
        $warranty = \App\Models\Warranty::with(['customer', 'product', 'purchaseSource'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('public.register.success', compact('warranty'));
    }

    /**
     * @param  array<string, mixed>  $prefill
     * @param  array<string, mixed>  $result
     */
    protected function resolveLocalProductIdFromPrefill(array $prefill, array $result): ?int
    {
        $candidate = $prefill['product_id'] ?? null;
        if ($candidate && Product::query()->whereKey($candidate)->exists()) {
            return (int) $candidate;
        }

        $odooProduct = $result['odoo']['product'] ?? [];
        $odooProductId = $odooProduct['odoo_product_id'] ?? null;

        $query = Product::query();
        if ($odooProductId) {
            $matched = $query->where('odoo_product_id', (string) $odooProductId)
                ->orWhere('odoo_id', (string) $odooProductId)
                ->first();
            if ($matched) {
                return (int) $matched->id;
            }
        }

        $name = $prefill['product_name'] ?? null;
        if ($name) {
            $matched = Product::query()->where('name', $name)->first();
            if ($matched) {
                return (int) $matched->id;
            }
        }

        return null;
    }

    /**
     * Map Odoo sale / POS config / stock location into registration purchase fields.
     *
     * @param  array<string, mixed>  $sale
     * @return array{purchase_source_id: int|null, branch_name: string|null, dealer_id: int|null, purchase_place_label: string|null}
     */
    protected function resolvePurchasePlace(array $sale): array
    {
        $rawBranch = $this->cleanOdooLocationLabel($sale['branch_name'] ?? null)
            ?: $this->cleanOdooLocationLabel($sale['current_location'] ?? null);
        $inStock = ($sale['sale_status'] ?? null) === 'in_stock';
        $hasSaleEvidence = $inStock
            || filled($sale['purchase_date'] ?? null)
            || filled($sale['invoice_number'] ?? null)
            || filled($sale['odoo_pos_order_id'] ?? null)
            || filled($rawBranch);

        $brandShop = PurchaseSource::query()->where('code', 'brand_shop')->first();
        $dealerSource = PurchaseSource::query()->where('code', 'dealer')->first();
        $jumia = PurchaseSource::query()->where('code', 'jumia')->first();
        $kilimall = PurchaseSource::query()->where('code', 'kilimall')->first();
        $other = PurchaseSource::query()->where('code', 'other')->first();

        $empty = [
            'purchase_source_id' => null,
            'branch_name' => null,
            'dealer_id' => null,
            'purchase_place_label' => null,
        ];

        if (! $hasSaleEvidence) {
            return $empty;
        }

        $lower = strtolower((string) $rawBranch);

        if ($rawBranch && (str_contains($lower, 'jumia'))) {
            return [
                'purchase_source_id' => $jumia?->id,
                'branch_name' => $rawBranch,
                'dealer_id' => null,
                'purchase_place_label' => 'Jumia',
            ];
        }

        if ($rawBranch && (str_contains($lower, 'kilimall'))) {
            return [
                'purchase_source_id' => $kilimall?->id,
                'branch_name' => $rawBranch,
                'dealer_id' => null,
                'purchase_place_label' => 'Kilimall',
            ];
        }

        if ($rawBranch) {
            foreach ($this->brandShopOptions() as $option) {
                if (strcasecmp($rawBranch, $option) === 0 || str_contains($lower, strtolower($option))) {
                    return [
                        'purchase_source_id' => $brandShop?->id,
                        'branch_name' => $option,
                        'dealer_id' => null,
                        'purchase_place_label' => $inStock
                            ? 'Current branch: '.$option
                            : 'Brand Shop: '.$option,
                    ];
                }
            }

            $dealer = Dealer::query()
                ->where('is_active', true)
                ->where(function ($query) use ($rawBranch) {
                    $query->where('physical_location', 'like', '%'.$rawBranch.'%')
                        ->orWhere('dealer_code', strtoupper($rawBranch))
                        ->orWhere('name', 'like', '%'.$rawBranch.'%');
                })
                ->orderBy('name')
                ->first();

            if ($dealer) {
                $isBrandShopDealer = str_contains(strtolower($dealer->name), 'brand shop')
                    || in_array(strtoupper((string) $dealer->dealer_code), ['SARIN', 'CBD', 'WESTLANDS'], true);

                if ($isBrandShopDealer) {
                    $branch = $this->normalizeBrandShopBranch(
                        $dealer->physical_location ?: $dealer->dealer_code ?: $dealer->name
                    ) ?: $rawBranch;

                    return [
                        'purchase_source_id' => $brandShop?->id,
                        'branch_name' => $branch,
                        'dealer_id' => null,
                        'purchase_place_label' => $inStock
                            ? 'Current branch: '.$branch
                            : 'Brand Shop: '.$branch,
                    ];
                }

                return [
                    'purchase_source_id' => $dealerSource?->id,
                    'branch_name' => null,
                    'dealer_id' => $dealer->id,
                    'purchase_place_label' => $inStock
                        ? 'Current seller: '.$dealer->name
                        : $dealer->name,
                ];
            }
        }

        // POS sale or current stock at a known branch defaults to Brand Shop (K-Elec tills / WH).
        if (filled($sale['odoo_pos_order_id'] ?? null) || $inStock) {
            $branch = $this->normalizeBrandShopBranch($rawBranch);

            return [
                'purchase_source_id' => $brandShop?->id,
                'branch_name' => $branch,
                'dealer_id' => null,
                'purchase_place_label' => $branch
                    ? ($inStock ? 'Current branch: '.$branch : 'Brand Shop: '.$branch)
                    : ($inStock ? 'In stock (branch unknown)' : 'Brand Shop'),
            ];
        }

        if ($rawBranch) {
            return [
                'purchase_source_id' => $other?->id ?? $brandShop?->id,
                'branch_name' => $rawBranch,
                'dealer_id' => null,
                'purchase_place_label' => $rawBranch,
            ];
        }

        return [
            'purchase_source_id' => $brandShop?->id,
            'branch_name' => null,
            'dealer_id' => null,
            'purchase_place_label' => 'Brand Shop',
        ];
    }

    protected function cleanOdooLocationLabel(?string $label): ?string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return null;
        }

        // Drop generic stock destinations that are not purchase outlets.
        $lower = strtolower($label);
        if (
            str_contains($lower, 'customers')
            || str_contains($lower, 'partners')
            || str_contains($lower, 'vendors')
            || str_contains($lower, 'inventory adjustment')
            || $lower === 'stock'
        ) {
            return null;
        }

        $cleaned = preg_replace('/^(pos|point of sale|kelec|k-elec|brand shop)[\s\-\/:|]*/i', '', $label) ?? $label;
        $cleaned = trim($cleaned, " \t\n\r\0\x0B-|/");

        return $cleaned !== '' ? $cleaned : $label;
    }

    /**
     * @return list<string>
     */
    protected function brandShopOptions(): array
    {
        $fromSettings = collect(explode(',', (string) $this->settingsService->get('pos_brand_shop_branches', 'Sarin,CBD')))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        $fromDealers = Dealer::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('name', 'like', '%Brand Shop%')
                    ->orWhereIn('dealer_code', ['SARIN', 'CBD', 'WESTLANDS']);
            })
            ->orderBy('name')
            ->get(['name', 'physical_location', 'dealer_code'])
            ->map(function (Dealer $dealer) {
                return trim((string) ($dealer->physical_location ?: $dealer->dealer_code ?: $dealer->name));
            })
            ->filter();

        return $fromSettings
            ->merge($fromDealers)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique(fn ($value) => strtolower($value))
            ->values()
            ->all();
    }

    protected function normalizeBrandShopBranch(?string $branch): ?string
    {
        $branch = $this->cleanOdooLocationLabel($branch);
        if ($branch === null || $branch === '') {
            return null;
        }

        foreach ($this->brandShopOptions() as $option) {
            if (strcasecmp($branch, $option) === 0 || str_contains(strtolower($branch), strtolower($option))) {
                return $option;
            }
        }

        $dealer = Dealer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($branch) {
                $query->where('physical_location', 'like', '%'.$branch.'%')
                    ->orWhere('dealer_code', strtoupper($branch))
                    ->orWhere('name', 'like', '%'.$branch.'%');
            })
            ->first();

        if ($dealer) {
            return trim((string) ($dealer->physical_location ?: $dealer->dealer_code ?: $dealer->name));
        }

        return $branch;
    }
}
