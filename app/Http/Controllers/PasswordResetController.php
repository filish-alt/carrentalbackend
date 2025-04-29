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

public function sendOtp($phone, $otp){
    try {
        $response = Http::withToken('your_api_token_here')->asForm()->post('https://api.geezsms.com/api/v1/sms/send', [
            'token' => 'your_api_token_here',
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
