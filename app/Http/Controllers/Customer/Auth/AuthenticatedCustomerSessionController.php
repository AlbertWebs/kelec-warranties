<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\PhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedCustomerSessionController extends Controller
{
    public function create(): View
    {
        return view('customer.auth.login');
    }

    public function store(Request $request, PhoneNumberService $phoneNumberService): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $normalized = $phoneNumberService->normalize($login);

        $customer = Customer::query()
            ->where(function ($query) use ($login, $normalized) {
                $query->whereRaw('LOWER(email) = ?', [strtolower($login)]);
                if ($normalized) {
                    $query->orWhere('mobile_normalized', $normalized);
                }
                $query->orWhere('mobile_number', $login);
            })
            ->first();

        if (! $customer || ! $customer->password || ! Hash::check($credentials['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        Auth::guard('customer')->login($customer, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('customer.warranties.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }
}
