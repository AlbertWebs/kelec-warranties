@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-brand-ink">Users</h1>
    <p class="mt-1 text-sm text-slate-500">Staff accounts and roles</p>
</div>

<form method="POST" action="{{ route('admin.users.store') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-5">
    @csrf
    <input name="name" required placeholder="Name" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="email" type="email" required placeholder="Email" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <input name="password" type="password" required placeholder="Password" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
    <select name="role" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
        @foreach ($roles as $role)
            <option value="{{ $role->name }}">{{ str_replace('_', ' ', $role->name) }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Create user</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Active</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="transition hover:bg-brand-soft/80">
                        <td class="px-4 py-3.5 align-top font-medium text-brand-ink">{{ $user->name }}</td>
                        <td class="px-4 py-3.5 align-top">{{ $user->email }}</td>
                        <td class="px-4 py-3.5 align-top capitalize">{{ $user->roles->pluck('name')->map(fn ($name) => str_replace('_', ' ', $name))->join(', ') }}</td>
                        <td class="px-4 py-3.5 align-top">
                            @if ($user->is_active)
                                <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Yes</span>
                            @else
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/20">No</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-16 text-center text-sm text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($users->hasPages())
    <div class="mt-4">{{ $users->links() }}</div>
@endif
@endsection
