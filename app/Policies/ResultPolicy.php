<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResultPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Test $test): bool
    {
        return $user->id === $test->user_id || $user->is_admin;
    }
}
