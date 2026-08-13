<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('action', 80)->index();
            $table->string('status', 40)->index();
            $table->string('query')->nullable();
            $table->string('reference')->nullable()->index();
            $table->string('result_summary')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        $permission = Permission::findOrCreate('activity_logs.view');

        Role::findOrCreate('super_admin')->givePermissionTo($permission);
        Role::findOrCreate('warranty_admin')->givePermissionTo($permission);
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        $permission = Permission::query()->where('name', 'activity_logs.view')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
