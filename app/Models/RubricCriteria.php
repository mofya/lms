<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricCriteria extends Model
{
    use HasFactory;

    protected $table = 'rubric_criteria';

    protected $fillable = [
        'rubric_id',
        'name',
        'description',
        'max_points',
        'position',
    ];

    protected $casts = [
        'max_points' => 'integer',
        'position' => 'integer',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }
}
