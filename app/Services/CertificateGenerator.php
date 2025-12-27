<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateGenerator
{
    /**
     * Generate a certificate for a user completing a course
     */
    public function generateCertificate(User $user, Course $course): Certificate
    {
        // Check if certificate already exists
        $existing = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create certificate record
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'certificate_number' => $this->generateUniqueCertificateNumber(),
            'issued_at' => now(),
        ]);

        // Generate PDF
        $pdfPath = $this->generatePdf($certificate);
        $certificate->update(['file_path' => $pdfPath]);

        return $certificate;
    }

    protected function generateUniqueCertificateNumber(): string
    {
        do {
            $number = 'CERT-' . strtoupper(Str::random(8)) . '-' . date('Y');
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    protected function generatePdf(Certificate $certificate): string
    {
        $user = $certificate->user;
        $course = $certificate->course;
        
        // Generate HTML content for certificate
        $html = view('certificates.template', [
            'certificate' => $certificate,
            'user' => $user,
            'course' => $course,
        ])->render();

        // For now, save as HTML file (can be upgraded to PDF with dompdf/barryvdh)
        $filename = 'certificates/' . $certificate->certificate_number . '.html';
        Storage::disk('public')->put($filename, $html);

        return $filename;
    }

    /**
     * Verify a certificate by certificate number
     */
    public function verifyCertificate(string $certificateNumber): ?Certificate
    {
        return Certificate::where('certificate_number', $certificateNumber)->first();
    }
}
