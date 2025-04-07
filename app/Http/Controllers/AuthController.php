<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Models\Users;
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
    $otp = rand(100000, 999999);
   

    // Create user
    $user = Users::create([
        'first_name'      => $request->first_name,
        'last_name'       => $request->last_name,
        'email'           => $request->email,
        'phone'           => $request->phone,
        'hash_password'   =>  Hash::make($request->password),
        'digital_id'      => $digitalIdPath,
        'driver_liscence' => $driverLiscencePath,
        'address'         => $request->address,
        'city'            => $request->city,
        'Birth_Date'      => $request->birth_date,
        'role'            => $request->role,
        'status'          => 'Pending',
        'otp'             => $otp,
        'otp_expires_at'  => now()->addMinutes(5),
    ]);
    
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
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $user,
        'token' => $token,
    ]);
}

}
