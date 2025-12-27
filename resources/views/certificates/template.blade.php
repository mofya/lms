<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Completion</title>
    <style>
        @page {
            size: letter landscape;
            margin: 0;
        }
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .certificate {
            background: white;
            padding: 60px;
            border: 10px solid #d4af37;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            text-align: center;
        }
        .header {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        .subtitle {
            font-size: 24px;
            color: #7f8c8d;
            margin-bottom: 40px;
        }
        .name {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin: 40px 0;
            padding: 20px;
            border-bottom: 3px solid #d4af37;
            border-top: 3px solid #d4af37;
        }
        .course {
            font-size: 28px;
            color: #34495e;
            margin: 30px 0;
        }
        .date {
            font-size: 18px;
            color: #7f8c8d;
            margin-top: 40px;
        }
        .certificate-number {
            font-size: 14px;
            color: #95a5a6;
            margin-top: 30px;
            font-family: monospace;
        }
        .signature {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
        }
        .signature-line {
            border-top: 2px solid #2c3e50;
            width: 200px;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">Certificate of Completion</div>
        <div class="subtitle">This is to certify that</div>
        
        <div class="name">{{ $user->name }}</div>
        
        <div class="subtitle">has successfully completed the course</div>
        
        <div class="course">{{ $course->title }}</div>
        
        <div class="date">
            Issued on {{ $certificate->issued_at->format('F j, Y') }}
        </div>
        
        <div class="signature">
            <div>
                <div class="signature-line"></div>
                <div style="margin-top: 10px;">Instructor</div>
            </div>
            <div>
                <div class="signature-line"></div>
                <div style="margin-top: 10px;">Date</div>
            </div>
        </div>
        
        <div class="certificate-number">
            Certificate Number: {{ $certificate->certificate_number }}
        </div>
    </div>
</body>
</html>
