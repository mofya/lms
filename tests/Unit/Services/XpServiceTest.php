<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpServiceTest extends TestCase
{
    use RefreshDatabase;

    private XpService $xpService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xpService = new XpService();
    }

    public function test_award_lesson_completion_adds_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);

        $this->xpService->awardLessonCompletion($user);

        $user->refresh();

        $this->assertEquals(XpService::XP_PER_LESSON, $user->xp_points);
    }

    public function test_award_quiz_completion_adds_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);

        $this->xpService->awardQuizCompletion($user, 5, 10);

        $user->refresh();

        $this->assertEquals(XpService::XP_PER_QUIZ, $user->xp_points);
    }

    public function test_award_quiz_completion_adds_bonus_for_perfect_score(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);

        $this->xpService->awardQuizCompletion($user, 10, 10);

        $user->refresh();

        $this->assertEquals(
            XpService::XP_PER_QUIZ + XpService::XP_PERFECT_SCORE_BONUS,
            $user->xp_points
        );
    }

    public function test_award_assignment_submission_adds_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);

        $this->xpService->awardAssignmentSubmission($user);

        $user->refresh();

        $this->assertEquals(XpService::XP_PER_ASSIGNMENT, $user->xp_points);
    }

    public function test_award_discussion_participation_adds_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);

        $this->xpService->awardDiscussionParticipation($user);

        $user->refresh();

        $this->assertEquals(XpService::XP_PER_DISCUSSION, $user->xp_points);
    }

    public function test_xp_increases_level(): void
    {
        $user = User::factory()->create(['xp_points' => 90, 'level' => 1]);

        $this->xpService->awardLessonCompletion($user); // +10 XP = 100 total

        $user->refresh();

        $this->assertEquals(100, $user->xp_points);
        $this->assertEquals(2, $user->level);
    }

    public function test_update_streak_starts_new_streak(): void
    {
        $user = User::factory()->create([
            'current_streak' => 0,
            'last_activity_date' => null,
        ]);

        $this->xpService->updateStreak($user);

        $user->refresh();

        $this->assertEquals(1, $user->current_streak);
        $this->assertNotNull($user->last_activity_date);
    }

    public function test_update_streak_increments_consecutive_day(): void
    {
        $user = User::factory()->create([
            'current_streak' => 5,
            'last_activity_date' => now()->subDay()->startOfDay(),
        ]);

        $this->xpService->updateStreak($user);

        $user->refresh();

        $this->assertEquals(6, $user->current_streak);
    }

    public function test_update_streak_resets_on_missed_day(): void
    {
        $user = User::factory()->create([
            'current_streak' => 10,
            'last_activity_date' => now()->subDays(2)->startOfDay(),
        ]);

        $this->xpService->updateStreak($user);

        $user->refresh();

        $this->assertEquals(1, $user->current_streak);
    }

    public function test_update_streak_awards_bonus_at_7_days(): void
    {
        $user = User::factory()->create([
            'xp_points' => 0,
            'current_streak' => 6,
            'last_activity_date' => now()->subDay()->startOfDay(),
        ]);

        $this->xpService->updateStreak($user);

        $user->refresh();

        $this->assertEquals(7, $user->current_streak);
        $this->assertEquals(XpService::XP_STREAK_BONUS, $user->xp_points);
    }

    public function test_update_streak_awards_multiplied_bonus_at_14_days(): void
    {
        $user = User::factory()->create([
            'xp_points' => 0,
            'current_streak' => 13,
            'last_activity_date' => now()->subDay()->startOfDay(),
        ]);

        $this->xpService->updateStreak($user);

        $user->refresh();

        $this->assertEquals(14, $user->current_streak);
        $this->assertEquals(XpService::XP_STREAK_BONUS * 2, $user->xp_points);
    }

    public function test_get_xp_for_next_level(): void
    {
        $user = User::factory()->create(['xp_points' => 150, 'level' => 2]);

        $xpNeeded = $this->xpService->getXpForNextLevel($user);

        // Level 2 requires 200 XP, user has 150, needs 50 more
        $this->assertEquals(50, $xpNeeded);
    }

    public function test_get_level_progress(): void
    {
        $user = User::factory()->create(['xp_points' => 150, 'level' => 2]);

        $progress = $this->xpService->getLevelProgress($user);

        // Level 2 is 100-199 XP, user has 150, which is 50% through the level
        $this->assertEquals(50.0, $progress);
    }
}
