<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Models\Users;


use Illuminate\Support\Facades\Redis;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;



class AuthController extends Controller
{
    public function register(Request $request) 
     { 
        Log::info('=== Incoming Request ===');
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'required|regex:/^09\d{8}$/|unique:users,phone', 
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

               
            $driverLiscencePath = $request->file('driver_liscence') 
                ? $request->file('driver_liscence')->store('driver_licences') 
                : null;

            $digitalIdPath = $request->file('digital_id') 
                ? $request->file('digital_id')->store('digital_ids') 
                : null;

            $passport = $request->file('passport') 
                ? $request->file('passport')->store('passport') 
                : null;
    $otp = rand(100000, 999999);
   

    // Create user
    $user = Users::create([
        'first_name'      => $request->first_name,
        'last_name'       => $request->last_name,
        'email'           => $request->email,
        'phone'           => $request->phone,
        'hash_password'   =>  Hash::make($request->password),
        'digital_id'      => $digitalIdPath,
        'passport'        => $passport,
        'driver_liscence' => $driverLiscencePath,
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
        'user' => $user
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



public function login(Request $request)
{
    $request->validate([
        'phone' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = Users::where('phone', $request->phone)->first();
    Log::info('=== Incoming Request ===');
    Log::info($request->all());
    
    if (! $user || ! Hash::check($request->password, $user->hash_password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
   
    if ($user->otp && $user->otp_expires_at && now()->lessThan($user->otp_expires_at)) {
        return response()->json(['message' => 'Phone number not verified. Please enter the OTP sent to your phone.'], 403);
    }

    if ($user->two_factor_enabled) {
        $otp = rand(100000, 999999);
        Redis::setex("2fa:{$user->id}", 300, $otp); // 5 mins expiry

        // Send OTP via email
        Mail::to($user->email)->send(new \App\Mail\TwoFactorCodeMail($otp));
       
        // Send via phone (SMS)
         Log::info("your 2fa code {$otp}");

        return response()->json([
            'message' => 'Two-factor code sent',
            'two_factor_pending' => true,
            'user_id' => $user->id,
        ]);
    }
    
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token,
    ]);
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

