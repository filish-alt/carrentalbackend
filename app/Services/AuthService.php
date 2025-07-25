<?php

namespace App\Services;

use App\Models\Users;
use App\Models\SuperAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AuthService
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
    
            Log::info("Attempting to send OTP to phone: {$phone}");
    
            $response = Http::asForm()->post('https://api.geezsms.com/api/v1/sms/send', [
                'token' => 'iE0L4t06lOKr3u2AmzFQ3d4nXe2DZpeC',
                'phone' => $phone,
                'msg'   => "Dear user, your OTP is: {$otp} It will expire in 5 minutes. Thank you!",
            ]);
    
            Log::info("SMS API Response:", ['response' => $response->json()]);
    
            $apiResponse = $response->json();
            if ($response->failed() || (isset($apiResponse['error']) && $apiResponse['error'])) { 
                Log::error('Failed to send OTP via SMS', [
                    'status' => $response->status(),
                    'response' => $apiResponse
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again later.',
                    'api_response' => $apiResponse
                ];
            }
    
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'api_response' => $apiResponse
            ];
    
        } catch (\Exception $e) {
            Log::error('Error occurred while sending OTP via SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'An error occurred while sending OTP. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
    }

    public function register(array $data, $request)
    {
        // Handle file uploads
        $digitalIdPath = $request->file('digital_id')
            ? $request->file('digital_id')->move(base_path('../public_html/digital_ids'), uniqid() . '.' 
            . $request->file('digital_id')->getClientOriginalExtension())
            : null;
 
        $driverLiscencePath = $request->file('driver_liscence')
         ? $request->file('driver_liscence')->move(base_path('../public_html/driver_licences'), uniqid() . '.' 
         . $request->file('driver_liscence')->getClientOriginalExtension())
         : null;
        
        $passport = $request->file('passport')
         ? $request->file('passport')->move(base_path('../public_html/passport'), uniqid() . '.' 
         . $request->file('passport')->getClientOriginalExtension())
         : null;

        $driverLiscenceUrl = $driverLiscencePath ? url('driver_licences/' . basename($driverLiscencePath)) : null;
        $digitalIdUrl = $digitalIdPath ? url('digital_ids/' . basename($digitalIdPath)) : null;
        $passportUrl = $passport ? url('passport/' . basename($passport)) : null;
     
        $otp = rand(100000, 999999);

        $sms_response = $this->sendOtp($data['phone'], $otp);
   
        // Create user
        $user = Users::create([
            'first_name'      => $data['first_name'],
            'middle_name'     => $data['middle_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'hash_password'   => Hash::make($data['password']),
            'digital_id'      => $digitalIdPath,
            'passport'        => $passport,
            'driver_licence'  => $driverLiscencePath,
            'address'         => $data['address'] ?? null,
            'city'            => $data['city'] ?? null,
            'birth_Date'      => $data['birth_date'] ?? null,
            'role'            => 'User',
            'status'          => 'Pending',
            'otp'             => $otp,
            'otp_expires_at'  => now()->addMinutes(5),
        ]);

        // Send OTP to email
        if ($user->email) {
            Mail::raw("Your registration OTP is: $otp", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Account Verification OTP');
            });
        }
        
        Log::info("OTP for {$user->phone}: {$otp}");
        
        return [
            'message' => 'User registered successfully.',
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'driver_licence' => $driverLiscenceUrl,
                'digital_id' => $digitalIdUrl,
                'passport' => $passportUrl,
                'address' => $user->address,
                'city' => $user->city,
                'birth_date' => $user->birth_date,
                'status' => $user->status,
               ],
            'sms_response' => $sms_response
        ];
    }

    public function verifyPhoneOtp($phone, $otp)
    {
        $user = Users::where('phone', $phone)
            ->where('otp', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return ['success' => true, 'message' => 'Phone number verified successfully.', 'user' => $user];
    }

    public function verifyEmailOtp($email, $otp)
    {
        $user = Users::where('email', $email)
            ->where('otp', $otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return ['success' => true, 'message' => 'Email verified successfully.', 'user' => $user];
    }

    public function login($loginInput, $password)
    {
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $adminQuery = SuperAdmin::query();
        $admin = $isEmail
            ? $adminQuery->where('email', $loginInput)->first()
            : $adminQuery->where('phone', $loginInput)->first();

        if ($admin && Hash::check($password, $admin->hash_password)) {
            $token = $admin->createToken('auth_token')->plainTextToken;
            return [
                'success' => true,
                'role' => 'admin',
                'user' => $admin,
                'token' => $token
            ];
        }

        $userQuery = Users::query();
        $user = $isEmail
            ? $userQuery->where('email', $loginInput)->first()
            : $userQuery->where('phone', $loginInput)->first();

        if (!$user || !Hash::check($password, $user->hash_password)) {
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if ($user->otp && $user->otp_expires_at) {
            return [
                'success' => false,
                'two_factor_pending' => true,
                'message' => 'Phone number not verified. Please enter the OTP sent to your phone.',
                'user_id' => $user->id
            ];
        }

        if ($user->two_factor_enabled) {
            $otp = rand(100000, 999999);
            $user->two_factor_code = $otp;
            $user->two_factor_expires_at = now()->addMinutes(5);
            $user->save();

            Mail::raw("Your two factor code is: $otp", function ($message) use ($user) {
                $message->to($user->email)->subject('Two factor Verification OTP');
            });

            $this->sendOtp($user->phone, $otp);

            return [
                'success' => false,
                'two_factor_pending' => true,
                'message' => 'Two-factor code sent',
                'user_id' => $user->id
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'role' => $user->role ?? 'user',
            'user' => $user,
            'token' => $token
        ];
    }

    public function logout($user)
    {
        $user->tokens()->delete();
        return ['message' => 'Logged out successfully.'];
    }

    public function updatePassword($user, $currentPassword, $newPassword)
    {
        if (!Hash::check($currentPassword, $user->hash_password)) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $user->hash_password = Hash::make($newPassword);
        $user->save();

        return ['success' => true, 'message' => 'Password updated successfully'];
    }
}
