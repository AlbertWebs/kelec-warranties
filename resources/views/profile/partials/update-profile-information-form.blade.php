<section>
    <header class="mb-5 border-b border-gray-100 pb-4">
        <h2 class="text-base font-semibold text-brand-ink">Profile information</h2>
        <p class="mt-1 text-sm text-gray-500">Update your display name and email address.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-3">
        @csrf
        @method('patch')

        <div class="auth-field">
            <label for="name" class="auth-label">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="auth-input"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Your full name"
            >
            <x-input-error class="!mt-0" :messages="$errors->get('name')" />
        </div>

        <div class="auth-field">
            <label for="email" class="auth-label">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                class="auth-input"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                placeholder="you@kelec.co.ke"
            >
            <x-input-error class="!mt-0" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="text-sm text-gray-700">
                    Your email address is unverified.
                    <button form="send-verification" class="font-semibold text-brand underline underline-offset-2 hover:text-brand-dark">
                        Resend verification email
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address.
                    </p>
                @endif
            @endif
        </div>

        <div class="pt-1">
            <x-primary-button>Save profile</x-primary-button>
        </div>
    </form>
</section>
