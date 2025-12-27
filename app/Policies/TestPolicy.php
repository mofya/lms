<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Test $test): bool
    {
        return $user->id === $test->user_id || $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Test $test): bool
    {
        return false;
    }
}
