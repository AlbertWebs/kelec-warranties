<section class="space-y-4">
    <header class="border-b border-red-100 pb-4">
        <h2 class="text-base font-semibold text-red-700">Delete account</h2>
        <p class="mt-1 text-sm text-gray-600">
            Permanently remove your staff account. This cannot be undone.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Delete account</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-brand-ink">
                Delete your account?
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                Enter your password to confirm. All profile data for this staff user will be permanently deleted.
            </p>

            <div class="auth-field mt-5">
                <label for="password" class="auth-label">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="auth-input"
                    placeholder="Your password"
                >
                <x-input-error :messages="$errors->userDeletion->get('password')" class="!mt-0" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    Cancel
                </x-secondary-button>
                <x-danger-button>
                    Delete account
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
