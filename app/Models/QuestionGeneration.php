<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'prompt_params',
        'questions_generated',
    ];

    protected $casts = [
        'prompt_params' => 'array',
        'questions_generated' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

