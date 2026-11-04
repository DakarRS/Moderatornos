<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends AdminOnlyPolicy
{
    public function delete(User $user, ?User $target = null): bool
    {
        return $user->isAdmin() && ! $target?->is($user);
    }
}
