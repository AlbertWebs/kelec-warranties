@extends('layouts.admin')

@section('title', 'Dealers')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-brand-ink">Dealers</h1>
    <p class="mt-1 text-sm text-slate-500">Authorised outlets and brand shops</p>
</div>

<form method="POST" action="{{ route('admin.dealers.store') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3">
    @csrf
    <input name="name" required placeholder="Dealer name" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="dealer_code" placeholder="Code" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="contact_person" placeholder="Contact person" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="mobile_number" placeholder="Mobile" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="email" placeholder="Email" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="county" placeholder="County" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="town" placeholder="Town" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="physical_location" placeholder="Location" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand md:col-span-2">
    <button class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Add dealer</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Town</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($dealers as $dealer)
                    <tr class="group transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $dealer->name }}</td>
                        <td class="px-4 py-3.5 align-top font-mono text-[13px] text-slate-600">{{ $dealer->dealer_code ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top">
                            <p>{{ $dealer->contact_person ?: '—' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $dealer->mobile_number }}</p>
                        </td>
                        <td class="px-4 py-3.5 align-top">{{ $dealer->town ?: '—' }}</td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($dealer->is_active)
                                <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
                            @else
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 align-top text-right">
                            <form method="POST" action="{{ route('admin.dealers.destroy', $dealer) }}">
                                @csrf @method('DELETE')
                                <button class="inline-flex rounded-md px-2 py-1 text-sm font-medium text-red-600 transition hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No dealers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($dealers->hasPages())
    <div class="mt-4">{{ $dealers->links() }}</div>
@endif
@endsection
