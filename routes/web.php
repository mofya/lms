<?php

use App\Http\Controllers\CourseController;
use App\Services\CertificateGenerator;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::group([
    'middleware' => ['guest'],
], function () {
    Route::get('/', [CourseController::class, 'index'])->name('home');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
});

// Certificate verification (public route)
Route::get('/certificates/verify/{certificateNumber}', function (string $certificateNumber) {
    $generator = new CertificateGenerator();
    $certificate = $generator->verifyCertificate($certificateNumber);
    
    if (!$certificate) {
        abort(404, 'Certificate not found');
    }
    
    return view('certificates.verification', [
        'certificate' => $certificate,
        'user' => $certificate->user,
        'course' => $certificate->course,
    ]);
})->name('certificates.verify');

// Allow graceful GET logout for admin panel to avoid MethodNotAllowed when manually visiting /admin/logout.
Route::get('/admin/logout', function () {
    Filament::auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/admin/login');
})->name('filament.admin.logout.get');
