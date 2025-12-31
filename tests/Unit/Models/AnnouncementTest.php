<?php

namespace Tests\Unit\Models;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $announcement = Announcement::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($announcement->course->is($course));
    }

    public function test_announcement_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($announcement->creator->is($user));
    }

    public function test_published_scope_filters_correctly(): void
    {
        // Published announcement (no dates)
        $published1 = Announcement::factory()->create();

        // Published announcement (past published_at)
        $published2 = Announcement::factory()->published()->create();

        // Scheduled announcement (future published_at)
        Announcement::factory()->scheduled()->create();

        // Expired announcement
        Announcement::factory()->expired()->create();

        $published = Announcement::published()->get();

        $this->assertCount(2, $published);
        $this->assertTrue($published->contains($published1));
        $this->assertTrue($published->contains($published2));
    }

    public function test_for_course_scope_includes_global_announcements(): void
    {
        $course = Course::factory()->create();
        $courseAnnouncement = Announcement::factory()->create(['course_id' => $course->id]);
        $globalAnnouncement = Announcement::factory()->global()->create();
        Announcement::factory()->create(); // Different course

        $announcements = Announcement::forCourse($course->id)->get();

        $this->assertCount(2, $announcements);
        $this->assertTrue($announcements->contains($courseAnnouncement));
        $this->assertTrue($announcements->contains($globalAnnouncement));
    }

    public function test_for_course_scope_returns_only_global_when_null(): void
    {
        $globalAnnouncement = Announcement::factory()->global()->create();
        Announcement::factory()->create(); // Course specific

        $announcements = Announcement::forCourse(null)->get();

        $this->assertCount(1, $announcements);
        $this->assertTrue($announcements->contains($globalAnnouncement));
    }

    public function test_pinned_scope_filters_correctly(): void
    {
        Announcement::factory()->create(['is_pinned' => false]);
        $pinned = Announcement::factory()->pinned()->create();

        $pinnedAnnouncements = Announcement::pinned()->get();

        $this->assertCount(1, $pinnedAnnouncements);
        $this->assertTrue($pinnedAnnouncements->contains($pinned));
    }

    public function test_is_published_returns_true_when_no_dates(): void
    {
        $announcement = Announcement::factory()->create([
            'published_at' => null,
            'expires_at' => null,
        ]);

        $this->assertTrue($announcement->isPublished());
    }

    public function test_is_published_returns_true_when_within_date_range(): void
    {
        $announcement = Announcement::factory()->create([
            'published_at' => now()->subHour(),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($announcement->isPublished());
    }

    public function test_is_published_returns_false_before_published_at(): void
    {
        $announcement = Announcement::factory()->scheduled()->create();

        $this->assertFalse($announcement->isPublished());
    }

    public function test_is_published_returns_false_after_expires_at(): void
    {
        $announcement = Announcement::factory()->expired()->create();

        $this->assertFalse($announcement->isPublished());
    }

    public function test_is_global_returns_true_when_no_course(): void
    {
        $announcement = Announcement::factory()->global()->create();

        $this->assertTrue($announcement->isGlobal());
    }

    public function test_is_global_returns_false_when_course_set(): void
    {
        $announcement = Announcement::factory()->create();

        $this->assertFalse($announcement->isGlobal());
    }
}
