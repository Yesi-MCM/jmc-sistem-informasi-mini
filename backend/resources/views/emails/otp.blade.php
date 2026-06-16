<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kode OTP Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-top: 4px solid #0d6efd;
        }
        h2 {
            color: #333;
            margin-top: 0;
        }
        p {
            color: #666;
            line-height: 1.5;
        }
        .otp-code {
            display: inline-block;
            font-size: 32px;
            font-weight: bold;
            color: #0d6efd;
            background-color: #e8f0fe;
            padding: 15px 30px;
            border-radius: 6px;
            letter-spacing: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi Login JMC Mini Portal</h2>
        <p>Halo,</p>
        <p>Anda menerima email ini karena ada permintaan login ke akun JMC Portal Anda. Gunakan kode verifikasi OTP di bawah ini untuk menyelesaikan proses login Anda:</p>
        <div class="otp-code">{{ $otpCode }}</div>
        <p><strong>Penting:</strong> Kode ini hanya berlaku selama <strong>3 menit</strong>. Jangan bagikan kode ini kepada siapapun.</p>
        <p>Jika Anda tidak melakukan permintaan login ini, abaikan email ini.</p>
        <div class="footer">
            Sistem Informasi Mini Portal HRD - JMC IT Consultant<br>
            Pesan ini dikirimkan secara otomatis oleh sistem. Mohon tidak membalas email ini.
        </div>
    </div>
</body>
</html>
