<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'trigger_type',
        'trigger_value',
        'is_active',
    ];

    protected $casts = [
        'trigger_value' => 'integer',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    public function checkAndAward(User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check if user already has this badge
        if ($user->badges()->where('badge_id', $this->id)->exists()) {
            return false;
        }

        // Check trigger conditions
        $shouldAward = match ($this->trigger_type) {
            'first_quiz' => $this->checkFirstQuiz($user),
            'first_assignment' => $this->checkFirstAssignment($user),
            'course_completed' => $this->checkCourseCompleted($user),
            'perfect_score' => $this->checkPerfectScore($user),
            'streak' => $this->checkStreak($user),
            'quizzes_completed' => $this->checkQuizzesCompleted($user),
            'assignments_completed' => $this->checkAssignmentsCompleted($user),
            'discussions_participated' => $this->checkDiscussionsParticipated($user),
            default => false,
        };

        if ($shouldAward) {
            $user->badges()->attach($this->id, ['earned_at' => now()]);
            return true;
        }

        return false;
    }

    protected function checkFirstQuiz(User $user): bool
    {
        return \App\Models\Test::where('user_id', $user->id)->exists();
    }

    protected function checkFirstAssignment(User $user): bool
    {
        return \App\Models\AssignmentSubmission::where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->exists();
    }

    protected function checkCourseCompleted(User $user): bool
    {
        $courses = \App\Models\Course::whereHas('students', fn($q) => $q->where('users.id', $user->id))
            ->published()
            ->get();

        foreach ($courses as $course) {
            $totalLessons = $course->publishedLessons()->count();
            $completedLessons = $user->completedLessons()
                ->where('course_id', $course->id)
                ->count();

            if ($totalLessons > 0 && $completedLessons >= $totalLessons) {
                return true;
            }
        }

        return false;
    }

    protected function checkPerfectScore(User $user): bool
    {
        $tests = \App\Models\Test::where('user_id', $user->id)->get();
        
        foreach ($tests as $test) {
            $totalQuestions = $test->quiz->questions()->count();
            if ($totalQuestions > 0 && $test->score === $totalQuestions) {
                return true;
            }
        }

        return false;
    }

    protected function checkStreak(User $user): bool
    {
        // Check for consecutive days with activity
        $days = $this->trigger_value ?? 7;
        $streak = 0;
        $currentDate = now()->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $checkDate = $currentDate->copy()->subDays($i);
            
            $hasActivity = \App\Models\Test::where('user_id', $user->id)
                    ->whereDate('created_at', $checkDate)
                    ->exists() ||
                \App\Models\AssignmentSubmission::where('user_id', $user->id)
                    ->whereDate('created_at', $checkDate)
                    ->exists() ||
                \App\Models\Lesson::whereHas('completedLessons', fn($q) => $q->where('users.id', $user->id))
                    ->whereDate('lesson_user.created_at', $checkDate)
                    ->exists();

            if ($hasActivity) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak >= $days;
    }

    protected function checkQuizzesCompleted(User $user): bool
    {
        $count = \App\Models\Test::where('user_id', $user->id)->distinct('quiz_id')->count('quiz_id');
        return $count >= ($this->trigger_value ?? 1);
    }

    protected function checkAssignmentsCompleted(User $user): bool
    {
        $count = \App\Models\AssignmentSubmission::where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->distinct('assignment_id')
            ->count('assignment_id');
        
        return $count >= ($this->trigger_value ?? 1);
    }

    protected function checkDiscussionsParticipated(User $user): bool
    {
        $count = \App\Models\DiscussionReply::where('user_id', $user->id)
            ->distinct('discussion_id')
            ->count('discussion_id');
        
        return $count >= ($this->trigger_value ?? 1);
    }
}
