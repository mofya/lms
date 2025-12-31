<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'certificate_number' => 'CERT-' . strtoupper(fake()->bothify('########')) . '-' . date('Y'),
            'file_path' => null,
            'issued_at' => now(),
        ];
    }
}
