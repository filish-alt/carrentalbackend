<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
        ]);
       
        $identifier = $request->identifier;
        $user = Users::where('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        //$token = Str::random(64);
        $code = rand(100000, 999999);
        Redis::setex('password_reset:' . $request->identifier, 3600, $code);
       
      //  $resetLink = url('/api/reset-password?token=' . $token . '&identifier=' . $identifier);
    
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            Log::info("OTP for {$user->email}: {$code}");
            // Mail::raw("Your code is: $otp", function ($message) use ($identifier) {
            //     $message->to($identifier)
            //             ->subject('Your Password Reset OTP');
            // });
        } else {
            $this->sendOtp($user->phone_number, $code);
            Log::info("OTP for {$user->phone_number}: {$code}");
            // Http::post('https://your-sms-api.com/send', [
            //     'phone' => $identifier,
            //     'message' => "Your password rest code is : $resetLink"
            // ]);
        }

        return response()->json([
            'message' => 'code sent successfully.',
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
        'identifier' => 'required',
        'code' => 'required|digits:6',
        'password' => 'required|confirmed|min:6',
    ]);

    $storedCode = Redis::get('password_reset:' . $request->identifier);

    if (! $storedCode || $storedCode != $request->code) {
        return response()->json(['error' => 'Invalid or expired code'], 400);
    }

    $user = Users::where('email', $request->identifier)
                    ->orWhere('phone', $request->identifier)
                    ->first();

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->hash_password = Hash::make($request->password); 
        $user->save();

        Redis::del('password_reset:' . $request->identifier);

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


public function sendOtp($phone, $otp){
    try {

        // Replace phone prefix
        if (str_starts_with($phone, '09')) {
            $phone = '2519' . substr($phone, 2);
        } elseif (str_starts_with($phone, '07')) {
            $phone = '2517' . substr($phone, 2);
        }

        $response = Http::asForm()->post('https://api.geezsms.com/api/v1/sms/send', [
            'token' => 'iE0L4t06lOKr3u2AmzFQ3d4nXe2DZpeC',
            'phone' => $phone,
            'msg'   => "Dear user, your OTP is: {$otp}. Use this code to complete your registration. It will expire in 5 minutes. Thank you!",
        ]);

        if ($response->failed()) {
            Log::error('Failed to send OTP via SMS', ['response' => $response->body()]);
            return response()->json(['message' => 'Failed to send OTP. Please try again later.'], 500);
        }
    } catch (\Exception $e) {
        Log::error('Error occurred while sending OTP via SMS', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'An error occurred while sending OTP. Please try again later.'], 500);
    }
}

}
