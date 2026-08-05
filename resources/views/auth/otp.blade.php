<x-guest-layout>
    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Step 2 of 2</p>
        <h1 class="mt-1 text-xl font-bold text-brand-ink sm:text-2xl">Enter verification code</h1>
        <p class="mt-1 text-sm text-gray-600">
            We sent a 6-digit code by SMS
            @if ($maskedMobile)
                to <span class="font-semibold text-brand-ink">{{ $maskedMobile }}</span>
            @endif
            and by email
            @if ($maskedEmail)
                to <span class="font-semibold text-brand-ink">{{ $maskedEmail }}</span>
            @else
                to your account email
            @endif.
        </p>
    </div>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login.otp.verify') }}" class="space-y-3">
        @csrf

        <div class="auth-field">
            <label for="otp" class="auth-label">Verification code</label>
            <input
                id="otp"
                class="auth-input tracking-[0.35em] text-center text-lg font-semibold"
                type="text"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                minlength="6"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="••••••"
            >
            <x-input-error :messages="$errors->get('otp')" class="!mt-0" />
        </div>

        <x-primary-button class="w-full justify-center">
            Verify and continue
        </x-primary-button>
    </form>

    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('login.otp.resend') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-brand hover:text-brand-dark underline underline-offset-2">
                Resend code (SMS & email)
            </button>
        </form>

        <form method="POST" action="{{ route('login.otp.cancel') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-brand-ink underline underline-offset-2">
                Back to login
            </button>
        </form>
    </div>
</x-guest-layout>
