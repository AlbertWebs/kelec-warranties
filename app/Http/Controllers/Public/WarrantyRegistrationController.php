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
    public function __construct(protected WarrantyRegistrationService $registrationService) {}

    public function create(Request $request): View
    {
        return view('public.register.index', [
            'purchaseSources' => PurchaseSource::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'dealers' => Dealer::query()->where('is_active', true)->orderBy('name')->get(),
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

        $prefill = [
            'serial_number' => $serial,
            'product_id' => $result['odoo']['product']['id'] ?? null,
            'product_name' => $result['odoo']['product']['name'] ?? null,
            'product_model' => $result['odoo']['product']['model'] ?? null,
            'product_category_id' => $result['odoo']['product']['category_id'] ?? null,
            'purchase_date' => $result['odoo']['sale']['purchase_date'] ?? null,
            'invoice_number' => $result['odoo']['sale']['invoice_number'] ?? null,
            'branch_name' => $result['odoo']['sale']['branch_name'] ?? null,
            'full_name' => $result['odoo']['customer']['full_name'] ?? null,
            'mobile_number' => $result['odoo']['customer']['mobile_number'] ?? null,
            'email' => $result['odoo']['customer']['email'] ?? null,
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
}
