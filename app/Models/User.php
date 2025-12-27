<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'xp_points',
        'level',
        'current_streak',
        'last_activity_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'xp_points' => 'integer',
            'level' => 'integer',
            'current_streak' => 'integer',
            'last_activity_date' => 'date',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Restrict admin panel by explicit role instead of email pattern
        if ($panel->getId() === 'admin') {
            return (bool) $this->is_admin;
        }

        // Student panel remains open to authenticated users
        return true;
    }

    public function teachingCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'lecturer_id');
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user');
    }
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_user', 'user_id', 'course_id');
    }
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class)->published();
    }
    public function completeLesson(Lesson $lesson): void
    {
        $this->completedLessons()->attach($lesson);
        $this->courses()->syncWithoutDetaching($lesson->course_id);
        
        // Award XP
        (new \App\Services\XpService())->awardLessonCompletion($this);
        (new \App\Services\XpService())->updateStreak($this);
        
        // Check if course is completed and issue certificate
        \App\Observers\CertificateObserver::checkCourseCompletion($this, $lesson->course);
        
        // Check for badge eligibility
        \App\Observers\BadgeObserver::checkBadges($this);
    }
    public function uncompleteLesson(Lesson $lesson): void
    {
        $this->completedLessons()->detach($lesson);
        $courseLessons = $lesson->course->lessons()->pluck('id')->toArray();

        if (! $this->completedLessons()->whereIn('id', $courseLessons)->exists()) {
            $this->courses()->detach($lesson->course_id);
        }
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
