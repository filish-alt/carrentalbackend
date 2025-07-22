<?php

namespace App\Services;

use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PasswordResetService
{
    public function sendOtp($phone, $otp)
    {
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
                'msg' => "Dear user, your Code is: {$otp}. It will expire in 5 minutes. Thank you!",
            ]);

            $apiResponse = $response->json();
            Log::info("SMS API Response:", ['response' => $response->json()]);

            if ($response->failed() || (isset($apiResponse['error']) && $apiResponse['error'])) {
                Log::error('Failed to send OTP via SMS', ['response' => $response->body()]);
                throw new \Exception('Failed to send OTP. Please try again later.', 500);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error occurred while sending OTP via SMS', ['error' => $e->getMessage()]);
            throw new \Exception('An error occurred while sending OTP. Please try again later.', 500);
        }
    }

    public function resendOtp($userId)
    {
        $user = Users::find($userId);

        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        // Check if previous OTP is still active
        if ($user->otp && $user->otp_expires_at && now()->lt($user->otp_expires_at)) {
            throw new \Exception('You already have a valid OTP. Please wait until it expires before resending.', 429);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        Log::info("OTP for {$user->phone}: {$otp}");
        $this->sendOtp($user->phone, $otp);

        // Send to email
        if ($user->email) {
            Mail::raw("Your registration OTP is: $otp", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Your Registration OTP');
            });
        }

        return ['message' => 'OTP resent successfully.'];
    }

    public function resendOtpWithIdentifier($phone)
    {
        $user = Users::where('phone', $phone)->first();
        
        if (!$user) {
            throw new \Exception('User not found.', 404);
        }

        // Check if previous OTP is still active
        if ($user->otp && $user->otp_expires_at && now()->lt($user->otp_expires_at)) {
            throw new \Exception('You already have a valid OTP. Please wait until it expires before resending.', 429);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        Log::info("OTP for {$user->phone}: {$otp}");
        $this->sendOtp($user->phone, $otp);

        return ['message' => 'OTP resent successfully.'];
    }

    public function forgotPassword($identifier)
    {
        $user = Users::where('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        $code = rand(100000, 999999);
        $user->otp = $code;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            Log::info("OTP for {$user->email}: {$code}");
            Mail::raw("Your reset code is: $code", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Reset Code');
            });
        } else {
            $this->sendOtp($user->phone, $code);
            Log::info("OTP for {$user->phone}: {$code}");
        }

        return ['message' => 'Code sent successfully.'];
    }

    public function resetPassword($identifier, $code, $password)
    {
        $user = Users::where('email', $identifier)
                   ->orWhere('phone', $identifier)
                   ->first();

        if (!$user) {
            throw new \Exception('User not found', 404);
        }

        // Check if OTP matches and not expired
        if ($user->otp != $code || now()->greaterThan($user->otp_expires_at)) {
            throw new \Exception('Invalid or expired code', 400);
        }

        $user->hash_password = Hash::make($password);
        $user->otp = null;  // Clear OTP
        $user->otp_expires_at = null;
        $user->save();

        return ['message' => 'Password reset successfully'];
    }

    public function resetPasswordWithOTP($email, $otp, $password)
    {
        $user = Users::where('email', $email)
            ->where('otp', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            throw new \Exception('Invalid or expired OTP.', 400);
        }

        $user->hash_password = Hash::make($password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return ['message' => 'Password reset successfully.'];
    }
}
