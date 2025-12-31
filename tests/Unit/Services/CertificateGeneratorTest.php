<?php

namespace Tests\Unit\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificateGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private CertificateGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CertificateGenerator;
        Storage::fake('public');
    }

    public function test_generate_certificate_creates_new_certificate(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $certificate = $this->generator->generateCertificate($user, $course);

        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals($user->id, $certificate->user_id);
        $this->assertEquals($course->id, $certificate->course_id);
        $this->assertNotNull($certificate->certificate_number);
        $this->assertNotNull($certificate->issued_at);
    }

    public function test_generate_certificate_returns_existing_if_already_exists(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $firstCertificate = $this->generator->generateCertificate($user, $course);
        $secondCertificate = $this->generator->generateCertificate($user, $course);

        $this->assertEquals($firstCertificate->id, $secondCertificate->id);
        $this->assertCount(1, Certificate::where('user_id', $user->id)->where('course_id', $course->id)->get());
    }

    public function test_certificate_number_follows_expected_format(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $certificate = $this->generator->generateCertificate($user, $course);

        $this->assertMatchesRegularExpression('/^CERT-[A-Z0-9]{8}-\d{4}$/', $certificate->certificate_number);
    }

    public function test_certificate_number_is_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create();

        $cert1 = $this->generator->generateCertificate($user1, $course);
        $cert2 = $this->generator->generateCertificate($user2, $course);

        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    public function test_generate_certificate_creates_file(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $certificate = $this->generator->generateCertificate($user, $course);

        $this->assertNotNull($certificate->file_path);
        Storage::disk('public')->assertExists($certificate->file_path);
    }

    public function test_generate_certificate_file_contains_user_and_course_info(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $course = Course::factory()->create(['title' => 'Advanced PHP']);

        $certificate = $this->generator->generateCertificate($user, $course);

        $content = Storage::disk('public')->get($certificate->file_path);

        $this->assertStringContainsString('John Doe', $content);
        $this->assertStringContainsString('Advanced PHP', $content);
    }

    public function test_verify_certificate_returns_certificate_for_valid_number(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $certificate = $this->generator->generateCertificate($user, $course);

        $verified = $this->generator->verifyCertificate($certificate->certificate_number);

        $this->assertInstanceOf(Certificate::class, $verified);
        $this->assertEquals($certificate->id, $verified->id);
    }

    public function test_verify_certificate_returns_null_for_invalid_number(): void
    {
        $verified = $this->generator->verifyCertificate('INVALID-12345678-2024');

        $this->assertNull($verified);
    }

    public function test_different_users_same_course_get_different_certificates(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $course = Course::factory()->create();

        $cert1 = $this->generator->generateCertificate($user1, $course);
        $cert2 = $this->generator->generateCertificate($user2, $course);

        $this->assertNotEquals($cert1->id, $cert2->id);
        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    public function test_same_user_different_courses_get_different_certificates(): void
    {
        $user = User::factory()->create();
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        $cert1 = $this->generator->generateCertificate($user, $course1);
        $cert2 = $this->generator->generateCertificate($user, $course2);

        $this->assertNotEquals($cert1->id, $cert2->id);
        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    public function test_issued_at_is_set_to_current_time(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->freezeTime();
        $certificate = $this->generator->generateCertificate($user, $course);

        $this->assertEquals(now()->toDateTimeString(), $certificate->issued_at->toDateTimeString());
    }
}
