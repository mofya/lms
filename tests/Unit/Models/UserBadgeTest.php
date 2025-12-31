<?php

namespace Tests\Unit\Models;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_badge_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $userBadge = UserBadge::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $userBadge->user);
        $this->assertEquals($user->id, $userBadge->user->id);
    }

    public function test_user_badge_belongs_to_badge(): void
    {
        $badge = Badge::factory()->create();
        $userBadge = UserBadge::factory()->create(['badge_id' => $badge->id]);

        $this->assertInstanceOf(Badge::class, $userBadge->badge);
        $this->assertEquals($badge->id, $userBadge->badge->id);
    }

    public function test_earned_at_is_cast_to_datetime(): void
    {
        $earnedAt = now();
        $userBadge = UserBadge::factory()->create(['earned_at' => $earnedAt]);

        $userBadge->refresh();

        $this->assertInstanceOf(Carbon::class, $userBadge->earned_at);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $badge = Badge::factory()->create();
        $earnedAt = now();

        $data = [
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'earned_at' => $earnedAt,
        ];

        $userBadge = UserBadge::create($data);

        $this->assertEquals($user->id, $userBadge->user_id);
        $this->assertEquals($badge->id, $userBadge->badge_id);
        $this->assertNotNull($userBadge->earned_at);
    }

    public function test_factory_creates_valid_user_badge(): void
    {
        $userBadge = UserBadge::factory()->create();

        $this->assertNotNull($userBadge->id);
        $this->assertNotNull($userBadge->user_id);
        $this->assertNotNull($userBadge->badge_id);
        $this->assertNotNull($userBadge->earned_at);
    }

    public function test_user_can_have_multiple_badges(): void
    {
        $user = User::factory()->create();
        $badge1 = Badge::factory()->create();
        $badge2 = Badge::factory()->create();
        $badge3 = Badge::factory()->create();

        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge1->id]);
        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge2->id]);
        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge3->id]);

        $this->assertCount(3, UserBadge::where('user_id', $user->id)->get());
    }

    public function test_badge_can_be_earned_by_multiple_users(): void
    {
        $badge = Badge::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        UserBadge::factory()->create(['user_id' => $user1->id, 'badge_id' => $badge->id]);
        UserBadge::factory()->create(['user_id' => $user2->id, 'badge_id' => $badge->id]);
        UserBadge::factory()->create(['user_id' => $user3->id, 'badge_id' => $badge->id]);

        $this->assertCount(3, UserBadge::where('badge_id', $badge->id)->get());
    }

    public function test_earned_at_can_be_set_to_specific_date(): void
    {
        $specificDate = Carbon::create(2024, 6, 15, 10, 30, 0);
        $userBadge = UserBadge::factory()->create(['earned_at' => $specificDate]);

        $userBadge->refresh();

        $this->assertEquals(2024, $userBadge->earned_at->year);
        $this->assertEquals(6, $userBadge->earned_at->month);
        $this->assertEquals(15, $userBadge->earned_at->day);
    }
}
