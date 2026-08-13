@php
    $activeTab = $activeTab ?? 'register';
@endphp

<div class="mb-6 flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm" role="tablist" aria-label="Warranty actions">
    <a href="{{ route('register-warranty.create') }}"
       role="tab"
       aria-selected="{{ $activeTab === 'register' ? 'true' : 'false' }}"
       class="flex-1 rounded-lg px-3 py-2.5 text-center text-sm font-semibold transition sm:px-4 {{ $activeTab === 'register' ? 'bg-brand text-white' : 'text-brand-ink hover:bg-brand-soft' }}">
        Register
    </a>
    <a href="{{ route('warranty.lookup') }}"
       role="tab"
       aria-selected="{{ $activeTab === 'lookup' ? 'true' : 'false' }}"
       class="flex-1 rounded-lg px-3 py-2.5 text-center text-sm font-semibold transition sm:px-4 {{ $activeTab === 'lookup' ? 'bg-brand text-white' : 'text-brand-ink hover:bg-brand-soft' }}">
        Lookup
    </a>
    <a href="{{ route('warranty.hub', ['tab' => 'claim']) }}"
       role="tab"
       aria-selected="{{ $activeTab === 'claim' ? 'true' : 'false' }}"
       class="flex-1 rounded-lg px-3 py-2.5 text-center text-sm font-semibold transition sm:px-4 {{ $activeTab === 'claim' ? 'bg-brand text-white' : 'text-brand-ink hover:bg-brand-soft' }}">
        Claim
    </a>
</div>
