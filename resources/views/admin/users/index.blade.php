@extends('layouts.admin')
@section('title', 'Users')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Users</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="mb-6 grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-5">
@csrf
<input name="name" required placeholder="Name" class="rounded-lg border-slate-300">
<input name="email" type="email" required placeholder="Email" class="rounded-lg border-slate-300">
<input name="password" type="password" required placeholder="Password" class="rounded-lg border-slate-300">
<select name="role" class="rounded-lg border-slate-300">@foreach($roles as $role)<option value="{{ $role->name }}">{{ $role->name }}</option>@endforeach</select>
<button class="rounded-lg bg-red-700 px-4 py-2 text-white">Create user</button>
</form>
<div class="overflow-x-auto rounded-xl border bg-white">
<table class="min-w-full text-sm">
<thead class="bg-slate-50 border-b"><tr><th class="px-4 py-3 text-left">Name</th><th class="px-4 py-3 text-left">Email</th><th class="px-4 py-3 text-left">Role</th><th class="px-4 py-3 text-left">Active</th></tr></thead>
<tbody>
@foreach($users as $user)
<tr class="border-b">
<td class="px-4 py-3">{{ $user->name }}</td>
<td class="px-4 py-3">{{ $user->email }}</td>
<td class="px-4 py-3">{{ $user->roles->pluck('name')->join(', ') }}</td>
<td class="px-4 py-3">{{ $user->is_active ? 'Yes' : 'No' }}</td>
</tr>
@endforeach
</tbody></table></div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
