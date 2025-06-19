<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Models\Users;
use App\Models\SuperAdmin;


use Illuminate\Support\Facades\Redis;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class AuthController extends Controller
{
    public function sendOtp($phone, $otp) {
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
                'msg'   => "Dear user, your OTP is: {$otp}. Use this code to complete your registration. It will expire in 5 minutes. Thank you!",
            ]);
    
            Log::info("SMS API Response:", ['response' => $response->json()]);
    
            if ($response->failed()) {
                Log::error('Failed to send OTP via SMS', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again later.',
                    'api_response' => $response->json()
                ];
            }
    
            // Return success response
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'api_response' => $response->json()
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
    
    public function register(Request $request) 
     { 
        Log::info('=== Incoming Request ===');
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'middle_name'        => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone' => ['required', 'regex:/^(09|07)\d{8}$/', 'unique:users,phone'],
            'password'         => 'required|string|min:6|confirmed', 
            'driver_liscence'  => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'digital_id'       => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'passport'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'address'          => 'nullable|string|max:255',
            'city'             => 'nullable|string|max:100',
            'birth_date'       => 'nullable|date',
            'role'             => 'nullable',
        ]);

        
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

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

    $sms_response = $this->sendOtp($request->phone, $otp);
   

    // Create user
    $user = Users::create([
        'first_name'      => $request->first_name,
        'middle_name'     => $request->middle_name,
        'last_name'       => $request->last_name,
        'email'           => $request->email,
        'phone'           => $request->phone,
        'hash_password'   =>  Hash::make($request->password),
        'digital_id'      => $digitalIdPath,
        'passport'        => $passport,
        'driver_licence' => $driverLiscencePath,
        'address'         => $request->address,
        'city'            => $request->city,
        'birth_Date'      => $request->birth_date,
        'role'            => $request->role,
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
    
    // Simulate sending OTP 
    Log::info("OTP for {$user->phone}: {$otp}");
    return response()->json([
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
    ], 201);
}
        

public function verifyPhoneOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|regex:/^09\d{8}$/',
        'otp'   => 'required|string|size:6',
    ]);

    $user = Users::where('phone', $request->phone)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    return response()->json([
        'message' => 'Phone number verified successfully.',
        'user' => $user,
    ]);
}

public function verifyEmailOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|string|size:6',
    ]);

    $user = Users::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    return response()->json([
        'message' => 'Email verified successfully.',
        'user' => $user,
    ]);
}



public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = Users::where('email', $request->email)->first();
    Log::info('=== Incoming Request ===');
    Log::info($request->all());
     $userType='';
     $admin = SuperAdmin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->hash_password)) {
                $user = $admin;
                $userType = 'admin';
            }

        if (!$user) {
            $normalUser = Users::where('email', $request->email)->first();
            if ($normalUser && Hash::check($request->password, $normalUser->hash_password)) {
                $user = $normalUser;
                $userType = 'user';
            }
        }
        if (!$user) {
            Log::warning('Invalid login credentials', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials.'], 401);
            
        }
   if ($userType === 'user') {
    if ($user->otp && $user->otp_expires_at && now()->lessThan($user->otp_expires_at)) {
        return response()->json(['message' => 'Phone number not verified. Please enter the OTP sent to your phone.'], 403);
    }

    if ($user->two_factor_enabled) {
        $otp = rand(100000, 999999);
        $this->sendOtp($request->phone, $otp);
        Redis::setex("2fa:{$user->id}", 300, $otp); // 5 mins expiry

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($otp));
       
        // Send via phone (SMS)
        
         $this->sendOtp($user->phone_number, $otp);
         Log::info("your 2fa code {$otp}");

        return response()->json([
            'message' => 'Two-factor code sent',
            'two_factor_pending' => true,
            'user_id' => $user->id,
        ]);
    }
}
    //Force logout: delete all old tokens before issuing a new one
    //$user->tokens()->delete();
       
        // Check for existing active token 
        //  $activeToken = $user->tokens()->first();
        //  if ($activeToken) {
        //     return $this->errorResponse('You are already logged in from another device. Please log out first to continue.', 
        //                                 null, 403);
        // }
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'role' => $user->role ?? $userType,
        'user' => $user,
        'token' => $token,
    ]);
}
public function logout(Request $request)
{
    $user = Auth::user();
    if ($user) {
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }
    return response()->json(['message' => 'User not found.'], 404);
}

public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed',
    ]);

    $user = Auth::user();
    echo $user->name;
    if (!Hash::check($request->current_password, $user->hash_password)) {
        return response()->json(['message' => 'Current password is incorrect'], 403);
    }

    $user->hash_password = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully']);
}

}

