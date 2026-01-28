<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>MU Connect - Password Reset</h1>
    </div>
    <div class="content">
        <h2>Hello {{ $user->first_name }} {{ $user->last_name }},</h2>
        
        <p>We received a request to reset your password for your MU Connect account.</p>
        
        <p>Click the button below to reset your password:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        </div>
        
        <p>Or copy and paste this URL into your browser:</p>
        <p style="word-break: break-all; color: #4F46E5;">{{ $resetUrl }}</p>
        
        <div class="warning">
            <strong>⚠️ Important:</strong>
            <ul>
                <li>This link will expire in 60 minutes</li>
                <li>If you didn't request a password reset, please ignore this email</li>
                <li>Never share this link with anyone</li>
            </ul>
        </div>
        
        <p>If you have any questions, please contact support.</p>
        
        <p>Best regards,<br>The MU Connect Team</p>
    </div>
    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} MU Connect. All rights reserved.</p>
    </div>
</body>
</html>
