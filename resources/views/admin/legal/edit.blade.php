@extends('layouts.admin')

@section('title', 'Legal Pages')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-brand-ink">Legal Pages</h1>
        <p class="mt-1 text-sm text-gray-600">Edit the public Privacy Policy and Warranty Terms. Content supports Markdown (headings, bold, links, lists).</p>
    </div>
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('privacy-policy') }}" target="_blank" rel="noopener" class="rounded-md border border-gray-300 bg-white px-3 py-2 hover:border-brand hover:text-brand">View Privacy Policy</a>
        <a href="{{ route('warranty-terms') }}" target="_blank" rel="noopener" class="rounded-md border border-gray-300 bg-white px-3 py-2 hover:border-brand hover:text-brand">View Warranty Terms</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.legal.update') }}" class="space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-brand-ink">Privacy Policy</h2>
            @if ($privacyUpdatedAt)
                <span class="text-xs text-gray-500">Last saved {{ \Illuminate\Support\Carbon::parse($privacyUpdatedAt)->diffForHumans() }}</span>
            @endif
        </div>
        <label class="mt-4 block text-sm font-medium text-gray-700">
            Public URL (optional canonical)
            <input type="url" name="privacy_policy_url" value="{{ old('privacy_policy_url', $privacyUrl) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                   placeholder="https://…/privacy-policy">
        </label>
        <label class="mt-4 block text-sm font-medium text-gray-700">
            Page content
            <textarea name="privacy_policy_content" rows="22" required
                      class="mt-1 w-full rounded-lg border-gray-300 font-mono text-sm leading-relaxed focus:border-brand focus:ring-brand"
                      placeholder="Use ## for section headings">{{ old('privacy_policy_content', $privacyContent) }}</textarea>
        </label>
        <p class="mt-2 text-xs text-gray-500">Tip: start sections with <code class="rounded bg-gray-100 px-1">## Heading</code>. Changes go live immediately after save.</p>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-brand-ink">Warranty Terms</h2>
            @if ($termsUpdatedAt)
                <span class="text-xs text-gray-500">Last saved {{ \Illuminate\Support\Carbon::parse($termsUpdatedAt)->diffForHumans() }}</span>
            @endif
        </div>
        <label class="mt-4 block text-sm font-medium text-gray-700">
            Public URL (optional canonical)
            <input type="url" name="warranty_terms_url" value="{{ old('warranty_terms_url', $termsUrl) }}"
                   class="mt-1 w-full rounded-lg border-gray-300 focus:border-brand focus:ring-brand"
                   placeholder="https://…/warranty-terms">
        </label>
        <label class="mt-4 block text-sm font-medium text-gray-700">
            Page content
            <textarea name="warranty_terms_content" rows="16" required
                      class="mt-1 w-full rounded-lg border-gray-300 font-mono text-sm leading-relaxed focus:border-brand focus:ring-brand">{{ old('warranty_terms_content', $termsContent) }}</textarea>
        </label>
    </section>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-dark">
            Save legal pages
        </button>
        <a href="{{ route('admin.settings.edit') }}" class="text-sm text-gray-600 hover:text-brand">Back to Settings</a>
    </div>
</form>
@endsection
