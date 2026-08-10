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
                    'redirect_url' => route('warranty.lookup', ['serial' => $serial]),
                ], 409);
            }

            return redirect()
                ->route('warranty.lookup')
                ->with('warning', $result['message'])
                ->with('lookup_serial', $serial);
        }

        $sale = is_array($result['odoo']['sale'] ?? null) ? $result['odoo']['sale'] : [];
        $branchName = $this->normalizeBrandShopBranch($sale['branch_name'] ?? null);
        $brandShopSource = PurchaseSource::query()->where('code', 'brand_shop')->first();
        $hasPosSale = filled($sale['purchase_date'] ?? null)
            || filled($sale['invoice_number'] ?? null)
            || filled($sale['odoo_pos_order_id'] ?? null)
            || filled($branchName);

        $prefill = [
            'serial_number' => $serial,
            'product_id' => $result['odoo']['product']['id'] ?? null,
            'product_name' => $result['odoo']['product']['name'] ?? null,
            'product_model' => $result['odoo']['product']['model'] ?? null,
            'product_category_id' => $result['odoo']['product']['category_id'] ?? null,
            'purchase_date' => $sale['purchase_date'] ?? null,
            'invoice_number' => $sale['invoice_number'] ?? null,
            'branch_name' => $branchName,
            'purchase_source_id' => $hasPosSale ? $brandShopSource?->id : null,
            'full_name' => $result['odoo']['customer']['full_name'] ?? null,
            'mobile_number' => $result['odoo']['customer']['mobile_number'] ?? null,
            'email' => $result['odoo']['customer']['email'] ?? null,
            'county' => $result['odoo']['customer']['county'] ?? null,
            'town' => $result['odoo']['customer']['town'] ?? null,
        ];

        $prefill['product_id'] = $this->resolveLocalProductIdFromPrefill($prefill, $result);

        if ($request->expectsJson()) {
            $status = (string) ($result['status'] ?? 'not_found');
            $validated = in_array($status, ['found', 'found_local'], true);

            return response()->json([
                'success' => true,
                'status' => $status,
                'validated' => $validated,
                'message' => $result['message'] ?? 'Serial check completed.',
                'prefill' => $prefill,
                'product' => [
                    'name' => $prefill['product_name'],
                    'model' => $prefill['product_model'],
                    'purchase_date' => $prefill['purchase_date'],
                    'invoice_number' => $prefill['invoice_number'],
                    'branch_name' => $prefill['branch_name'],
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
                'lookup_url' => route('warranty.lookup', ['reference' => $warranty->reference]),
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
                    ->orWhereIn('dealer_code', ['SARIN', 'CBD']);
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
        $branch = trim((string) $branch);
        if ($branch === '') {
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
