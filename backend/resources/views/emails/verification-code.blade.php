<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: system-ui, sans-serif; color: #1f2937; line-height: 1.6; }
        .container { max-width: 480px; margin: 0 auto; padding: 24px; }
        .code { font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #15803d; margin: 16px 0; }
        .note { color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Apollo Green Solutions</h2>
        <p>Thanks for signing up. Use the verification code below to finish creating your account:</p>
        <p class="code">{{ $code }}</p>
        <p>This code expires in 30 minutes. If you did not request it, you can ignore this email.</p>
        <p class="note">This is an automated message — please do not reply.</p>
    </div>
</body>
</html>