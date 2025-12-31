<?php

namespace Tests\Unit\Models;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_belongs_to_discussion(): void
    {
        $discussion = Discussion::factory()->create();
        $reply = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);

        $this->assertTrue($reply->discussion->is($discussion));
    }

    public function test_reply_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $reply = DiscussionReply::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($reply->user->is($user));
    }

    public function test_reply_belongs_to_parent(): void
    {
        $discussion = Discussion::factory()->create();
        $parent = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);
        $child = DiscussionReply::factory()->asReplyTo($parent)->create();

        $this->assertTrue($child->parent->is($parent));
    }

    public function test_reply_has_many_replies(): void
    {
        $discussion = Discussion::factory()->create();
        $parent = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);
        $child = DiscussionReply::factory()->asReplyTo($parent)->create();

        $this->assertTrue($parent->replies->contains($child));
    }

    public function test_increment_discussion_replies_count(): void
    {
        $discussion = Discussion::factory()->create(['replies_count' => 0]);
        $reply = DiscussionReply::factory()->create(['discussion_id' => $discussion->id]);

        $reply->incrementDiscussionRepliesCount();

        $discussion->refresh();

        $this->assertEquals(1, $discussion->replies_count);
        $this->assertNotNull($discussion->last_reply_at);
    }
}
