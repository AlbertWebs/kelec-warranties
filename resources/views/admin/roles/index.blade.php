@extends('layouts.admin')
@section('title', 'Roles')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Roles and Permissions</h1>
<div class="space-y-6">
@foreach($roles as $role)
<section class="rounded-xl border bg-white p-4 shadow-sm">
<h2 class="font-semibold">{{ $role->name }}</h2>
@if($role->name === 'super_admin')
<p class="mt-2 text-sm text-slate-500">Super administrator has all permissions.</p>
@else
<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-3">
@csrf @method('PUT')
<div class="grid gap-2 md:grid-cols-3">
@foreach($permissions as $permission)
<label class="flex items-center gap-2 text-sm">
<input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->permissions->contains('name', $permission->name))>
{{ $permission->name }}
</label>
@endforeach
</div>
<button class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-white">Save permissions</button>
</form>
@endif
</section>
@endforeach
</div>
@endsection
