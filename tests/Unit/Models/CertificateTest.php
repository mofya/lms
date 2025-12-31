<?php

namespace Tests\Unit\Models;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $certificate = Certificate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($certificate->user->is($user));
    }

    public function test_certificate_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $certificate = Certificate::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($certificate->course->is($course));
    }

    public function test_generate_certificate_number_creates_unique_number(): void
    {
        $certificate = Certificate::factory()->create();

        $number = $certificate->generateCertificateNumber();

        $this->assertStringStartsWith('CERT-', $number);
        $this->assertStringEndsWith('-' . date('Y'), $number);
    }

    public function test_get_verification_url_returns_correct_url(): void
    {
        $certificate = Certificate::factory()->create([
            'certificate_number' => 'CERT-ABC12345-2024',
        ]);

        $url = $certificate->getVerificationUrl();

        $this->assertStringContainsString('/certificates/verify/CERT-ABC12345-2024', $url);
    }
}
