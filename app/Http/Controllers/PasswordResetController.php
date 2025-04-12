<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Users;
use Illuminate\Http\Request;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $token = Str::random(64);

        Redis::setex('password_reset:' . $request->email, 3600, $token); 

       
        return response()->json([
            'message' => 'Reset link sent.',
            'reset_link' => url('/api/reset-password?token=' . $token . '&email=' . $request->email)
        ]);
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = Users::where('email', $request->email)->first();
        $otp = rand(100000, 999999);

        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Send OTP to email
        Mail::raw("Your OTP for password reset is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset OTP');
        });

        return response()->json(['message' => 'OTP sent to your email address.'], 200);
    }

 public function resetPassword(Request $request)
 {
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'token' => 'required',
        'password' => 'required|confirmed|min:6',
    ]);

    $storedToken = Redis::get('password_reset:' . $request->email);

    if (! $storedToken || ! hash_equals($storedToken, $request->token)) {
        return response()->json(['error' => 'Invalid or expired token'], 400);
    }

    $user = Users::where('email', $request->email)->first();
    $user->hash_password = Hash::make($request->password);
    $user->save();

    Redis::del('password_reset:' . $request->email);

    return response()->json(['message' => 'Password reset successfully']);
}
public function resetPasswordWithOTP(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|string|size:6',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = Users::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    $user->hash_password = Hash::make($request->new_password);
    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    return response()->json(['message' => 'Password reset successfully.'], 200);
}


}
