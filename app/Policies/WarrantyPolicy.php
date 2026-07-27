<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warranty;

class WarrantyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warranties.view');
    }

    public function view(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.view');
    }

    public function update(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.update');
    }

    public function approve(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.approve');
    }

    public function reject(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.reject');
    }

    public function delete(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('warranties.export');
    }

    public function resendNotification(User $user, Warranty $warranty): bool
    {
        return $user->can('warranties.resend_notification');
    }
}
