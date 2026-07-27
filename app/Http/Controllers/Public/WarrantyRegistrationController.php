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

    public function checkSerial(SerialCheckRequest $request): RedirectResponse
    {
        $result = $this->registrationService->checkSerial($request->validated('serial_number'));

        if (($result['status'] ?? null) === 'existing_active') {
            return redirect()
                ->route('warranty.lookup')
                ->with('warning', $result['message'])
                ->with('lookup_serial', strtoupper($request->validated('serial_number')));
        }

        $prefill = [
            'serial_number' => strtoupper($request->validated('serial_number')),
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

        return redirect()
            ->route('register-warranty.create')
            ->with('serial_result', $result)
            ->with('registration_prefill', $prefill)
            ->with('status', $result['message']);
    }

    public function store(WarrantyRegistrationRequest $request): RedirectResponse
    {
        $warranty = $this->registrationService->register(
            $request->validated(),
            $request->file('receipt')
        );

        $request->session()->forget(['registration_prefill', 'serial_result']);

        return redirect()
            ->route('register-warranty.success', $warranty->reference)
            ->with('success', 'Your warranty registration has been submitted successfully.');
    }

    public function success(string $reference): View
    {
        $warranty = \App\Models\Warranty::with(['customer', 'product', 'purchaseSource'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('public.register.success', compact('warranty'));
    }
}
