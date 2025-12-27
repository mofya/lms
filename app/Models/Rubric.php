<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'type',
        'freeform_text',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriteria::class)->orderBy('position');
    }

    public function isStructured(): bool
    {
        return $this->type === 'structured';
    }

    public function isFreeform(): bool
    {
        return $this->type === 'freeform';
    }

    public function getTotalPoints(): int
    {
        if ($this->isFreeform()) {
            return $this->assignment->max_points;
        }

        return $this->criteria->sum('max_points');
    }
}
