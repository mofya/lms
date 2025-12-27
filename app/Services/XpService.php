<?php

namespace App\Services;

use App\Models\User;

class XpService
{
    const XP_PER_LESSON = 10;
    const XP_PER_QUIZ = 25;
    const XP_PER_ASSIGNMENT = 30;
    const XP_PER_DISCUSSION = 5;
    const XP_PERFECT_SCORE_BONUS = 50;
    const XP_STREAK_BONUS = 10;

    const XP_PER_LEVEL = 100; // XP needed per level

    /**
     * Award XP for completing a lesson
     */
    public function awardLessonCompletion(User $user): void
    {
        $xp = self::XP_PER_LESSON;
        $this->addXp($user, $xp, 'Lesson completed');
    }

    /**
     * Award XP for completing a quiz
     */
    public function awardQuizCompletion(User $user, int $score, int $totalQuestions): void
    {
        $xp = self::XP_PER_QUIZ;
        
        // Bonus for perfect score
        if ($score === $totalQuestions && $totalQuestions > 0) {
            $xp += self::XP_PERFECT_SCORE_BONUS;
        }
        
        $this->addXp($user, $xp, 'Quiz completed');
    }

    /**
     * Award XP for submitting an assignment
     */
    public function awardAssignmentSubmission(User $user): void
    {
        $xp = self::XP_PER_ASSIGNMENT;
        $this->addXp($user, $xp, 'Assignment submitted');
    }

    /**
     * Award XP for participating in discussion
     */
    public function awardDiscussionParticipation(User $user): void
    {
        $xp = self::XP_PER_DISCUSSION;
        $this->addXp($user, $xp, 'Discussion participation');
    }

    /**
     * Update streak and award bonus
     */
    public function updateStreak(User $user): void
    {
        $today = now()->startOfDay();
        $lastActivity = $user->last_activity_date ? \Carbon\Carbon::parse($user->last_activity_date)->startOfDay() : null;

        if ($lastActivity === null) {
            // First activity
            $user->update([
                'current_streak' => 1,
                'last_activity_date' => $today,
            ]);
        } elseif ($lastActivity->equalTo($today->copy()->subDay())) {
            // Consecutive day
            $newStreak = $user->current_streak + 1;
            $user->update([
                'current_streak' => $newStreak,
                'last_activity_date' => $today,
            ]);

            // Award streak bonus
            if ($newStreak > 0 && $newStreak % 7 === 0) {
                $this->addXp($user, self::XP_STREAK_BONUS * ($newStreak / 7), "{$newStreak}-day streak bonus");
            }
        } elseif ($lastActivity->lessThan($today->copy()->subDay())) {
            // Streak broken
            $user->update([
                'current_streak' => 1,
                'last_activity_date' => $today,
            ]);
        }
    }

    /**
     * Add XP to user and check for level up
     */
    protected function addXp(User $user, int $xp, string $reason = ''): void
    {
        $newXp = $user->xp_points + $xp;
        $oldLevel = $user->level;
        $newLevel = $this->calculateLevel($newXp);

        $user->update([
            'xp_points' => $newXp,
            'level' => $newLevel,
        ]);

        // Check for level up
        if ($newLevel > $oldLevel) {
            $this->handleLevelUp($user, $oldLevel, $newLevel);
        }
    }

    /**
     * Calculate level based on XP
     */
    protected function calculateLevel(int $xp): int
    {
        return max(1, floor($xp / self::XP_PER_LEVEL) + 1);
    }

    /**
     * Handle level up event
     */
    protected function handleLevelUp(User $user, int $oldLevel, int $newLevel): void
    {
        // Could send notification here
        // For now, just update the level
    }

    /**
     * Get XP needed for next level
     */
    public function getXpForNextLevel(User $user): int
    {
        $currentLevelXp = ($user->level - 1) * self::XP_PER_LEVEL;
        $nextLevelXp = $user->level * self::XP_PER_LEVEL;
        return $nextLevelXp - $user->xp_points;
    }

    /**
     * Get progress percentage to next level
     */
    public function getLevelProgress(User $user): float
    {
        $currentLevelXp = ($user->level - 1) * self::XP_PER_LEVEL;
        $nextLevelXp = $user->level * self::XP_PER_LEVEL;
        $xpInCurrentLevel = $user->xp_points - $currentLevelXp;
        $xpNeededForLevel = $nextLevelXp - $currentLevelXp;

        return ($xpInCurrentLevel / $xpNeededForLevel) * 100;
    }
}
