<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionTest extends TestCase
{
    use RefreshDatabase;

    public function test_discussion_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $discussion = Discussion::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($discussion->course->is($course));
    }

    public function test_discussion_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $discussion = Discussion::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($discussion->user->is($user));
    }

    public function test_discussion_has_many_replies(): void
    {
        $discussion = Discussion::factory()->create();
        $reply = DiscussionReply::factory()->create([
            'discussion_id' => $discussion->id,
            'parent_id' => null,
        ]);

        $this->assertTrue($discussion->replies->contains($reply));
    }

    public function test_replies_excludes_nested_replies(): void
    {
        $discussion = Discussion::factory()->create();
        $topLevelReply = DiscussionReply::factory()->create([
            'discussion_id' => $discussion->id,
            'parent_id' => null,
        ]);
        DiscussionReply::factory()->asReplyTo($topLevelReply)->create();

        // Only top-level replies should be returned
        $this->assertCount(1, $discussion->replies);
    }

    public function test_all_replies_includes_nested(): void
    {
        $discussion = Discussion::factory()->create();
        $topLevelReply = DiscussionReply::factory()->create([
            'discussion_id' => $discussion->id,
            'parent_id' => null,
        ]);
        $nestedReply = DiscussionReply::factory()->asReplyTo($topLevelReply)->create();

        $this->assertCount(2, $discussion->allReplies);
        $this->assertTrue($discussion->allReplies->contains($nestedReply));
    }

    public function test_best_answer_relationship(): void
    {
        $discussion = Discussion::factory()->create();
        $reply = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);

        $discussion->update(['best_answer_id' => $reply->id]);

        $this->assertTrue($discussion->bestAnswer->is($reply));
    }

    public function test_mark_best_answer_updates_discussion_and_reply(): void
    {
        $discussion = Discussion::factory()->create();
        $reply = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);

        $discussion->markBestAnswer($reply);

        $discussion->refresh();
        $reply->refresh();

        $this->assertEquals($reply->id, $discussion->best_answer_id);
        $this->assertTrue($reply->is_best_answer);
    }

    public function test_mark_best_answer_unmarks_previous(): void
    {
        $discussion = Discussion::factory()->create();
        $oldBestAnswer = DiscussionReply::factory()->bestAnswer()->create([
            'discussion_id' => $discussion->id,
        ]);
        $discussion->update(['best_answer_id' => $oldBestAnswer->id]);

        $newBestAnswer = DiscussionReply::factory()->create([
            'discussion_id' => $discussion->id,
        ]);

        $discussion->markBestAnswer($newBestAnswer);

        $oldBestAnswer->refresh();
        $newBestAnswer->refresh();

        $this->assertFalse($oldBestAnswer->is_best_answer);
        $this->assertTrue($newBestAnswer->is_best_answer);
    }
}
