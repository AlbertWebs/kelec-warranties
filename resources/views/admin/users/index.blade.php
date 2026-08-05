@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-brand-ink">Users</h1>
    <p class="mt-1 text-sm text-slate-500">Staff accounts, roles, and SMS OTP mobile numbers</p>
</div>

@if ($canManageUsers)
    <form method="POST" action="{{ route('admin.users.store') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
        @csrf
        <input name="name" required placeholder="Name" value="{{ old('name') }}" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
        <input name="email" type="email" required placeholder="Email" value="{{ old('email') }}" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
        <input name="mobile_number" required placeholder="Mobile (OTP)" value="{{ old('mobile_number') }}" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
        <input name="password" type="password" required placeholder="Password" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
        <select name="role" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
            @foreach ($roles as $role)
                <option value="{{ $role->name }}">{{ str_replace('_', ' ', $role->name) }}</option>
            @endforeach
        </select>
        <button class="rounded-lg bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-ink">Create user</button>
    </form>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Staff account</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    @if ($canManageUsers)
                        <tr class="align-top transition hover:bg-brand-soft/80">
                            <td class="px-4 py-3.5">
                                <div class="grid gap-2 lg:grid-cols-7 lg:items-center">
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="contents">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" required value="{{ old('name', $user->name) }}" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                                        <input name="email" type="email" required value="{{ old('email', $user->email) }}" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                                        <input name="mobile_number" required value="{{ old('mobile_number', $user->mobile_number) }}" placeholder="07…" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                                        <select name="role" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand focus:ring-brand">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->name }}" @selected($user->hasRole($role->name))>{{ str_replace('_', ' ', $role->name) }}</option>
                                            @endforeach
                                        </select>
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="rounded border-slate-300 text-brand focus:ring-brand">
                                            Active
                                        </label>
                                        <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-brand-ink hover:border-brand hover:text-brand">Save</button>
                                    </form>
                                    @unless (auth()->id() === $user->id)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            class="contents"
                                            onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 transition hover:border-red-300 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @else
                        <tr class="transition hover:bg-brand-soft/80">
                            <td class="px-4 py-3.5">
                                <div class="grid gap-1 sm:grid-cols-4 sm:gap-4">
                                    <p class="font-medium text-brand-ink">{{ $user->name }}</p>
                                    <p>{{ $user->email }}</p>
                                    <p class="font-mono text-[13px] text-slate-600">{{ $user->mobile_number ?: '—' }}</p>
                                    <p class="capitalize">
                                        {{ $user->roles->pluck('name')->map(fn ($name) => str_replace('_', ' ', $name))->join(', ') }}
                                        ·
                                        @if ($user->is_active)
                                            <span class="text-emerald-700">Active</span>
                                        @else
                                            <span class="text-slate-500">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td class="px-4 py-16 text-center text-sm text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($users->hasPages())
    <div class="mt-4">{{ $users->links() }}</div>
@endif
@endsection
