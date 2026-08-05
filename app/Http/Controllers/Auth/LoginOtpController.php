<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AdminLoginOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginOtpController extends Controller
{
    public function __construct(protected AdminLoginOtpService $otpService) {}

    public function create(): View|RedirectResponse
    {
        $user = $this->otpService->pendingUser();
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.otp', [
            'maskedMobile' => $this->otpService->maskedMobileForPending(),
            'email' => $user->email,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $result = $this->otpService->verify($data['otp']);

        Auth::login($result['user'], $result['remember']);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resend(Request $request): RedirectResponse
    {
        $this->otpService->resend();

        return back()->with('status', 'A new verification code has been sent by SMS.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->otpService->clearChallenge();

        return redirect()->route('login')->with('status', 'Sign-in cancelled.');
    }
}
