<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'file_path',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function generateCertificateNumber(): string
    {
        return 'CERT-' . strtoupper(substr(md5($this->user_id . $this->course_id . time()), 0, 8)) . '-' . date('Y');
    }

    public function getVerificationUrl(): string
    {
        return url("/certificates/verify/{$this->certificate_number}");
    }
}
