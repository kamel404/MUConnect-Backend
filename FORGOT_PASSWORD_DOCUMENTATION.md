# Forget Password Feature - API Documentation

## Overview
The forget password feature allows users to reset their password by receiving a secure reset link via email. The feature includes token-based security with expiration and throttling mechanisms.

## Endpoints

### 1. Request Password Reset
**Endpoint:** `POST /api/forgot-password`

**Description:** Sends a password reset link to the user's email address.

**Request Body:**
```json
{
  "email": "user@mu.edu.lb"
}
```

**Validation Rules:**
- `email`: required, must be a valid email, must exist in users table

**Success Response (200):**
```json
{
  "message": "Password reset link has been sent to your email."
}
```

**Error Responses:**
- **422 Unprocessable Entity** - Validation failed
  ```json
  {
    "message": "The email field is required.",
    "errors": {
      "email": ["The email field is required."]
    }
  }
  ```
- **429 Too Many Requests** - Rate limit exceeded (1 request per minute)
  ```json
  {
    "message": "Please wait before requesting another password reset link."
  }
  ```
- **500 Internal Server Error** - Email sending failed
  ```json
  {
    "message": "Failed to send password reset email. Please try again later."
  }
  ```

---

### 2. Verify Reset Token
**Endpoint:** `POST /api/verify-reset-token`

**Description:** Verifies if a password reset token is valid and not expired.

**Request Body:**
```json
{
  "email": "user@mu.edu.lb",
  "token": "the-reset-token-from-email"
}
```

**Validation Rules:**
- `email`: required, must be a valid email
- `token`: required, must be a string

**Success Response (200):**
```json
{
  "message": "Token is valid.",
  "valid": true
}
```

**Error Responses:**
- **404 Not Found** - Token not found
  ```json
  {
    "message": "Invalid or expired reset token.",
    "valid": false
  }
  ```
- **400 Bad Request** - Token expired or invalid
  ```json
  {
    "message": "Reset token has expired. Please request a new one.",
    "valid": false
  }
  ```
  or
  ```json
  {
    "message": "Invalid reset token.",
    "valid": false
  }
  ```

---

### 3. Reset Password
**Endpoint:** `POST /api/reset-password`

**Description:** Resets the user's password using the valid token.

**Request Body:**
```json
{
  "email": "user@mu.edu.lb",
  "token": "the-reset-token-from-email",
  "password": "newPassword123",
  "password_confirmation": "newPassword123"
}
```

**Validation Rules:**
- `email`: required, must be a valid email
- `token`: required, must be a string
- `password`: required, minimum 8 characters, must be confirmed
- `password_confirmation`: must match password

**Success Response (200):**
```json
{
  "message": "Password has been reset successfully. Please login with your new password."
}
```

**Error Responses:**
- **404 Not Found** - Token or user not found
  ```json
  {
    "message": "Invalid or expired reset token."
  }
  ```
  or
  ```json
  {
    "message": "User not found."
  }
  ```
- **400 Bad Request** - Token expired or invalid
  ```json
  {
    "message": "Reset token has expired. Please request a new one."
  }
  ```
  or
  ```json
  {
    "message": "Invalid reset token."
  }
  ```
- **422 Unprocessable Entity** - Validation failed
  ```json
  {
    "message": "The password field confirmation does not match.",
    "errors": {
      "password": ["The password field confirmation does not match."]
    }
  }
  ```

---

## Security Features

### 1. Token Security
- Tokens are hashed using bcrypt before storage
- Each token is 64 characters long (random string)
- Tokens are single-use (deleted after successful password reset)

### 2. Token Expiration
- Tokens expire after **60 minutes**
- Expired tokens are automatically deleted when checked

### 3. Rate Limiting
- Users can only request a new reset link once per minute
- Prevents spam and abuse

### 4. Session Revocation
- All user tokens (API tokens) are revoked after successful password reset
- Forces re-authentication on all devices for security

---

## Email Template

The password reset email includes:
- User's full name
- A "Reset Password" button with the reset URL
- Plain text copy of the reset URL
- Security warnings about link expiration (60 minutes)
- Instructions to ignore if not requested
- Professional branding and footer

---

## Frontend Integration

### Reset URL Format
The reset link sent to users follows this format:
```
{FRONTEND_URL}/reset-password?token={TOKEN}&email={EMAIL}
```

### Environment Configuration
Add to your `.env` file:
```env
FRONTEND_URL=http://localhost:3000
```

For production, update to your actual frontend URL:
```env
FRONTEND_URL=https://your-frontend-domain.com
```

### Frontend Flow

1. **Forgot Password Page**
   - User enters their email
   - Call `POST /api/forgot-password`
   - Show success message

2. **Email Link**
   - User clicks link in email
   - Extracts token and email from URL parameters

3. **Reset Password Page**
   - Optional: Call `POST /api/verify-reset-token` to verify token validity
   - Show form for new password and confirmation
   - Call `POST /api/reset-password`
   - Redirect to login on success

### Example Frontend Implementation (React/Vue/Angular)

```javascript
// Forgot Password Request
async function requestPasswordReset(email) {
  try {
    const response = await fetch('/api/forgot-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      alert(data.message);
    } else {
      alert(data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Reset Password
async function resetPassword(email, token, password, passwordConfirmation) {
  try {
    const response = await fetch('/api/reset-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email,
        token,
        password,
        password_confirmation: passwordConfirmation
      })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      alert(data.message);
      // Redirect to login
      window.location.href = '/login';
    } else {
      alert(data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

## Database Schema

### password_reset_tokens Table
```sql
CREATE TABLE password_reset_tokens (
  email VARCHAR(255) PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
);
```

---

## Testing

### Manual Testing with Postman/cURL

1. **Request Password Reset:**
```bash
curl -X POST http://localhost:8000/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"user@mu.edu.lb"}'
```

2. **Verify Token (get token from email):**
```bash
curl -X POST http://localhost:8000/api/verify-reset-token \
  -H "Content-Type: application/json" \
  -d '{
    "email":"user@mu.edu.lb",
    "token":"token-from-email"
  }'
```

3. **Reset Password:**
```bash
curl -X POST http://localhost:8000/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email":"user@mu.edu.lb",
    "token":"token-from-email",
    "password":"newPassword123",
    "password_confirmation":"newPassword123"
  }'
```

---

## Troubleshooting

### Email Not Sending
1. Check mail configuration in `.env`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-app-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=noreply@mu.edu.lb
   MAIL_FROM_NAME="MU Connect"
   ```

2. Check Laravel logs: `storage/logs/laravel.log`

3. Test email configuration:
   ```bash
   php artisan tinker
   Mail::raw('Test email', function($message) {
       $message->to('test@mu.edu.lb')->subject('Test');
   });
   ```

### Token Not Found/Invalid
1. Ensure migration has been run: `php artisan migrate`
2. Check database for `password_reset_tokens` table
3. Verify token hasn't expired (60 minutes)

### Rate Limiting Issues
- Wait at least 1 minute between reset requests for the same email
- Check `password_reset_tokens` table for recent entries

---

## Files Created/Modified

### Created Files:
1. `database/migrations/2026_01_25_000000_create_password_reset_tokens_table.php`
2. `app/Mail/ResetPasswordMail.php`
3. `resources/views/emails/reset-password.blade.php`

### Modified Files:
1. `app/Http/Controllers/AuthController.php` - Added 3 methods:
   - `forgotPassword()`
   - `verifyResetToken()`
   - `resetPassword()`
2. `routes/api.php` - Added 3 routes
3. `config/app.php` - Added `frontend_url` config

---

## Best Practices

1. **Always use HTTPS in production** to secure password reset links
2. **Set appropriate FRONTEND_URL** in production environment
3. **Monitor failed reset attempts** for potential abuse
4. **Consider adding CAPTCHA** to forgot password form to prevent abuse
5. **Log all password reset activities** for security auditing
6. **Notify users via email** when password is successfully changed
7. **Consider implementing 2FA** as an additional security layer

---

## Future Enhancements

- Add SMS-based password reset option
- Implement CAPTCHA on forgot password endpoint
- Add email notification when password is successfully changed
- Track and limit failed reset attempts
- Add admin dashboard to monitor password reset activities
- Implement password history to prevent reuse of recent passwords
