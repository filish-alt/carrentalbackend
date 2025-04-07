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

}
