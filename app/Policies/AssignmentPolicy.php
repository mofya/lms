<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Assignment $assignment): bool
    {
        // Admins can always view
        if ($user->is_admin) {
            return true;
        }

        // Students can view if published and available
        return $assignment->isAvailable() && $assignment->course->students->contains($user);
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->is_admin;
    }

    public function submit(User $user, Assignment $assignment): bool
    {
        // Only students can submit
        if ($user->is_admin) {
            return false;
        }

        return $assignment->canSubmit($user);
    }
}
