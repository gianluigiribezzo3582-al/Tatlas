<?php

namespace App\Policies;

use App\Models\Studio;
use App\Models\User;

class StudioPolicy
{
    public function update(User $user, Studio $studio): bool
    {
        return $user->isAdmin() || $studio->claimed_by_user_id === $user->id;
    }
}
