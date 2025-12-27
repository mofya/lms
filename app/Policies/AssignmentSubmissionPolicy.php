<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssignmentSubmissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // Admins see all, students see their own (filtered in resource)
    }

    public function view(User $user, AssignmentSubmission $submission): bool
    {
        // Admins can view all
        if ($user->is_admin) {
            return true;
        }

        // Students can only view their own submissions
        return $submission->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Students can create submissions
        return !$user->is_admin;
    }

    public function update(User $user, AssignmentSubmission $submission): bool
    {
        // Students can update their own draft submissions
        if (!$user->is_admin && $submission->user_id === $user->id) {
            return $submission->status === 'draft';
        }

        // Admins can update any submission
        return $user->is_admin;
    }

    public function delete(User $user, AssignmentSubmission $submission): bool
    {
        // Students can delete their own draft submissions
        if (!$user->is_admin && $submission->user_id === $user->id) {
            return $submission->status === 'draft';
        }

        // Admins can delete any submission
        return $user->is_admin;
    }

    public function grade(User $user, AssignmentSubmission $submission): bool
    {
        // Only admins can grade
        return $user->is_admin;
    }

    public function approve(User $user, AssignmentSubmission $submission): bool
    {
        // Only admins can approve grades
        return $user->is_admin;
    }
}
