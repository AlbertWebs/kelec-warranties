<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\PhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredCustomerController extends Controller
{
    public function create(): View
    {
        return view('customer.auth.register');
    }

    public function store(Request $request, PhoneNumberService $phoneNumberService): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $normalized = $phoneNumberService->normalize($validated['mobile_number']);
        if (! $normalized) {
            throw ValidationException::withMessages([
                'mobile_number' => 'Enter a valid Kenyan mobile number.',
            ]);
        }

        $existingByMobile = Customer::query()->where('mobile_normalized', $normalized)->first();
        $existingByEmail = Customer::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
            ->first();

        if ($existingByMobile && $existingByEmail && $existingByMobile->isNot($existingByEmail)) {
            throw ValidationException::withMessages([
                'email' => 'This email belongs to a different customer account than this mobile number.',
            ]);
        }

        $customer = $existingByMobile ?: $existingByEmail;

        if ($customer?->hasPortalAccount()) {
            throw ValidationException::withMessages([
                'email' => 'An account already exists for this email or mobile. Please log in.',
            ]);
        }

        if ($customer) {
            $customer->update([
                'full_name' => $validated['full_name'],
                'mobile_number' => $validated['mobile_number'],
                'mobile_normalized' => $normalized,
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        } else {
            $customer = Customer::query()->create([
                'full_name' => $validated['full_name'],
                'mobile_number' => $validated['mobile_number'],
                'mobile_normalized' => $normalized,
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.warranties.index')
            ->with('success', 'Welcome! Your customer account is ready.');
    }
}
