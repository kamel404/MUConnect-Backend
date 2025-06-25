<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class VerificationController extends Controller
{
    /**
     * Verify the user's email.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Check that hash matches email
        if (! hash_equals($hash, sha1($user->email))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        // Mark as verified if not already
        if (! $user->is_verified) {
            $user->is_verified = true;
            $user->save();
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Email verified successfully']);
        }

        return view('auth.email_verified');
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->is_verified) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        \Mail::to($user->email)->send(new \App\Mail\VerifyEmail($user, $verificationUrl));

        return response()->json(['message' => 'Verification email resent']);
    }
}
