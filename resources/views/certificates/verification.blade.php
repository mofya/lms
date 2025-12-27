<!DOCTYPE html>
<html>
<head>
    <title>Certificate Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .verification-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .verified {
            color: #10b981;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .info {
            margin: 15px 0;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="verified">✓ Certificate Verified</div>
        
        <div class="info">
            <span class="label">Certificate Number:</span>
            {{ $certificate->certificate_number }}
        </div>
        
        <div class="info">
            <span class="label">Student Name:</span>
            {{ $user->name }}
        </div>
        
        <div class="info">
            <span class="label">Course:</span>
            {{ $course->title }}
        </div>
        
        <div class="info">
            <span class="label">Issued Date:</span>
            {{ $certificate->issued_at->format('F j, Y') }}
        </div>
    </div>
</body>
</html>
