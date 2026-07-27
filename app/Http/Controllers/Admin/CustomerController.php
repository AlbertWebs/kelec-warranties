<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\PhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('customers.view'), 403);

        $customers = Customer::query()
            ->withCount(['warranties', 'warranties as active_warranties_count' => fn ($q) => $q->where('status', 'active')])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q');
                $normalized = app(PhoneNumberService::class)->normalize((string) $term);
                $q->where(function ($query) use ($term, $normalized) {
                    $query->where('full_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('mobile_number', 'like', "%{$term}%");
                    if ($normalized) {
                        $query->orWhere('mobile_normalized', $normalized);
                    }
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(Request $request, Customer $customer): View
    {
        abort_unless($request->user()->can('customers.view'), 403);

        $customer->load([
            'warranties.product',
            'consents',
            'notificationLogs' => fn ($q) => $q->latest()->limit(20),
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->can('customers.update'), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'county' => ['nullable', 'string', 'max:100'],
            'town' => ['nullable', 'string', 'max:100'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $normalized = app(PhoneNumberService::class)->normalize($data['mobile_number']);

        $customer->update([
            ...$data,
            'mobile_normalized' => $normalized,
            'marketing_consent' => $request->boolean('marketing_consent'),
            'marketing_consent_at' => $request->boolean('marketing_consent') ? now() : $customer->marketing_consent_at,
        ]);

        return back()->with('success', 'Customer updated.');
    }
}
