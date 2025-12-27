<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'content',
        'is_pinned',
        'is_locked',
        'best_answer_id',
        'replies_count',
        'last_reply_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'replies_count' => 'integer',
        'last_reply_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(DiscussionReply::class)->whereNull('parent_id')->orderBy('created_at');
    }

    public function allReplies(): HasMany
    {
        return $this->hasMany(DiscussionReply::class)->orderBy('created_at');
    }

    public function bestAnswer(): BelongsTo
    {
        return $this->belongsTo(DiscussionReply::class, 'best_answer_id');
    }

    public function markBestAnswer(DiscussionReply $reply): void
    {
        // Unmark previous best answer if exists
        if ($this->best_answer_id) {
            DiscussionReply::where('id', $this->best_answer_id)->update(['is_best_answer' => false]);
        }

        // Mark new best answer
        $reply->update(['is_best_answer' => true]);
        $this->update(['best_answer_id' => $reply->id]);
    }
}
