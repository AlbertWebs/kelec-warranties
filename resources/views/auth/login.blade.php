<x-guest-layout>
    <div class="mb-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-brand">Staff access</p>
        <h1 class="mt-1 text-xl font-bold text-brand-ink sm:text-2xl">Staff login</h1>
        <p class="mt-1 text-sm text-gray-600">Sign in with email and password, then confirm the SMS and email verification code.</p>
    </div>

    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-3" x-data="{ showPassword: false }">
        @csrf

        <div class="auth-field">
            <label for="email" class="auth-label">Email address</label>
            <input
                id="email"
                class="auth-input"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="you@kelec.co.ke"
            >
            <x-input-error :messages="$errors->get('email')" class="!mt-0" />
        </div>

        <div class="auth-field">
            <label for="password" class="auth-label">Password</label>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Your password"
                    class="auth-input pr-11"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition hover:text-brand-navy focus:outline-none focus-visible:text-brand"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    :aria-pressed="showPassword.toString()"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="!mt-0" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-brand shadow-sm focus:ring-brand" name="remember">
                <span class="ms-2 text-sm text-gray-600">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-brand hover:text-brand-dark underline underline-offset-2" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            Log in
        </x-primary-button>
    </form>
</x-guest-layout>
