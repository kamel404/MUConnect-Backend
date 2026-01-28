<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user and return a token
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'username'   => 'required|unique:users,username',
            'email' => [
                'required',
                'email',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (!str_ends_with($value, '@mu.edu.lb')) {
                        $fail('The email must be an email address with the domain @mu.edu.lb.');
                    }
                },
            ],

            'password' => 'required|string|min:8|confirmed',

            // Add faculty and major validation
            'faculty_id' => 'required|exists:faculties,id',
            'major_id' => 'required|exists:majors,id',

            // Optional: Add validation to ensure major belongs to selected faculty
            'major_id' => [
                'required',
                'exists:majors,id',
                function ($attribute, $value, $fail) use ($request) {
                    $major = \App\Models\Major::find($value);
                    if ($major && $major->faculty_id != $request->faculty_id) {
                        $fail('The selected major does not belong to the selected faculty.');
                    }
                }
            ],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'faculty_id' => $validated['faculty_id'],
            'major_id' => $validated['major_id'],
            'is_active' => true,
        ]);

        // Assign default student role
        $studentRole = Role::where('name', 'student')->first();
        if ($studentRole) {
            $user->assignRole($studentRole);
        }

        // Create token with abilities based on role
        $token = $user->createToken('auth_token', $user->getRoleNames())->plainTextToken;

        // Send verification email
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
        try {
            \Mail::to($user->email)->send(new \App\Mail\VerifyEmail($user, $verificationUrl));
        } catch (\Throwable $e) {
            \Log::error('Failed to send verification email: '.$e->getMessage());
        }

        try {
            $systemUser = User::where('email', 'system@mu.edu.lb')->first();
            Notification::create([
                'user_id' => $user->id,
                'sender_id' => $systemUser?->id,
                'type' => 'welcome',
                'data' => [
                    'message' => 'Welcome to the community, ' . $user->first_name . '!',
                    'url' => url('/dashboard'),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to create welcome notification: '.$e->getMessage());
        }

        // Load relationships for response
        $user->load(['faculty', 'major', 'roles']);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Login user and return a token
     */
    public function login(Request $request)
    {
        // Validate that the login identifier and password are provided
        $request->validate([
            'login'    => 'required',
            'password' => 'required',
        ]);

        // Retrieve the login input and determine if it's an email or username
        $loginInput = $request->input('login');
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Build credentials array
        $credentials = [
            $loginField => $loginInput,
            'password'  => $request->input('password')
        ];

        // Attempt to authenticate
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = User::where($loginField, $loginInput)->firstOrFail();

        // Ensure the user has verified their email and has active account
        if (! $user->is_verified) {
            return response()->json(['message' => 'Please verify your email address before logging in.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Your account has been deactivated. Please contact admin for assistance.'], 403);
        }

        // No token deletion here, so multiple tokens can exist per user
        $token = $user->createToken('auth_token', $user->getRoleNames())->plainTextToken;

        return response()->json([
            'message' => 'Login Successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }


    /**
     * Logout user and revoke token
     */
    public function logout(Request $request)
    {
        // Revoke only the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Send password reset link to user's email
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Check if there's a recent password reset request (throttling)
        $recentReset = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->where('created_at', '>', Carbon::now()->subMinutes(1))
            ->first();

        if ($recentReset) {
            return response()->json([
                'message' => 'Please wait before requesting another password reset link.'
            ], 429);
        }

        // Generate a secure random token
        $token = Str::random(64);

        // Store the token in the database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Get the user
        $user = User::where('email', $validated['email'])->first();

        // Create the reset URL (you'll need to adjust this based on your frontend URL)
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($validated['email']);

        // Send the email
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $resetUrl));
            
            return response()->json([
                'message' => 'Password reset link has been sent to your email.'
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Failed to send password reset email: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to send password reset email. Please try again later.'
            ], 500);
        }
    }

    /**
     * Verify if password reset token is valid
     */
    public function verifyResetToken(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        // Find the token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
                'valid' => false
            ], 404);
        }

        // Check if token has expired (60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            
            return response()->json([
                'message' => 'Reset token has expired. Please request a new one.',
                'valid' => false
            ], 400);
        }

        // Verify the token
        if (!Hash::check($validated['token'], $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid reset token.',
                'valid' => false
            ], 400);
        }

        return response()->json([
            'message' => 'Token is valid.',
            'valid' => true
        ], 200);
    }

    /**
     * Reset user password using the token
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$resetRecord) {
            return response()->json([
                'message' => 'Invalid or expired reset token.'
            ], 404);
        }

        // Check if token has expired (60 minutes)
        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
            
            return response()->json([
                'message' => 'Reset token has expired. Please request a new one.'
            ], 400);
        }

        // Verify the token
        if (!Hash::check($validated['token'], $resetRecord->token)) {
            return response()->json([
                'message' => 'Invalid reset token.'
            ], 400);
        }

        // Find the user and update password
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }

        // Update the user's password
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        // Optionally, revoke all user's tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. Please login with your new password.'
        ], 200);
    }
}
