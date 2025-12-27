<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $quiz->is_published || $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }
}
