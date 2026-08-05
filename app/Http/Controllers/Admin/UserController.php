<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(protected PhoneNumberService $phoneNumberService) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('users.view') || $request->user()->can('users.manage'), 403);

        $users = User::with('roles')->latest()->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'canManageUsers' => $request->user()->can('users.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'mobile_number' => ['required', 'string', 'max:30'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $normalized = $this->phoneNumberService->normalize($data['mobile_number']);
        if (! $normalized || strlen($normalized) < 12) {
            return back()->withInput()->withErrors(['mobile_number' => 'Enter a valid Kenyan mobile number.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'],
            'mobile_normalized' => $normalized,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'mobile_number' => ['required', 'string', 'max:30'],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $normalized = $this->phoneNumberService->normalize($data['mobile_number']);
        if (! $normalized || strlen($normalized) < 12) {
            return back()->withInput()->withErrors(['mobile_number' => 'Enter a valid Kenyan mobile number.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile_number' => $data['mobile_number'],
            'mobile_normalized' => $normalized,
            'is_active' => $request->boolean('is_active', true),
            'password' => ! empty($data['password']) ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->can('users.manage'), 403);

        if ($request->user()->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'You cannot delete the last Super Administrator.');
            }
        }

        $name = $user->name;
        $userId = (int) $user->id;
        $modelType = addslashes($user->getMorphClass());
        $rolesTable = config('permission.table_names.model_has_roles');
        $permissionsTable = config('permission.table_names.model_has_permissions');

        // Avoid MySQL 1615 on shared hosts by skipping native prepared statements for this delete.
        DB::transaction(function () use ($userId, $modelType, $rolesTable, $permissionsTable) {
            DB::unprepared("DELETE FROM `{$rolesTable}` WHERE `model_id` = {$userId} AND `model_type` = '{$modelType}'");
            DB::unprepared("DELETE FROM `{$permissionsTable}` WHERE `model_id` = {$userId} AND `model_type` = '{$modelType}'");

            if (Schema::hasTable('sessions')) {
                DB::unprepared("DELETE FROM `sessions` WHERE `user_id` = {$userId}");
            }

            DB::unprepared("DELETE FROM `users` WHERE `id` = {$userId}");
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "User {$name} deleted.");
    }
}
