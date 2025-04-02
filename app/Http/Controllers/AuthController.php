<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request) 
     {
        $request ->validate([
            'first_name'=> 'required|string',
            'last_name'=> 'required|string',
            'email'=> 'required|string',
            'phone'=> 'required|string',
            'address'=> 'nullable|string',
            'city'=> 'required|string',
            'birth_date'=> 'required|date',
            'digtal_id' => 'required|file|mimes:jpg,png,pdf|max:2048', // Limit to 2MB
            'license' => 'nullable|file|mimes:jpg,png,pdf|max:2048' // Limit to 2MB
    
       ]);

        $otp = rand(100000, 999999); 
        $otpExpiry = now()->addMinutes(10);

        // Store the uploaded file in the 'public/scanning_ids' directory
        $filePath = $request->file('digtal_id') 
        ? $request->file('digtal_id')->store('digtal_ids', 'public') 
        : null;
       
        
        $licensePath = $request->file('license')
            ? $request->file('license')->store('licenses', 'public')
            : null;
        $user = Users::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'user' => $request->first_name,
            'address' => $request->address,
            'city' => $request->city,
            'birth_date' => $request->birth_date, 
            'license' => $licensePath,
            'otp' => $otp,
            'otp_expires_at' => $otpExpiry,
            'digtal_id' =>$filePath,
        ]);

        //replace the real otp service
        $response = Http::post('https://example-otp-api.com/send', [
            'phone' => $request->phone,
            'otp' => $otp
        ]);
        return response()->json(['message' => 'OTP sent to your phone.', 'otp' => $otp]);
        }

  public function verifyOtp(Request $request){
    $request->validate([
        'phone' => 'required|string',
        'otp' => 'required|digits:6'
    ]);

    $user = Users::where('phone', $request->phone)
                    ->where('otp', $request->otp)
                    ->where('otp_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid OTP or expired.'], 400);
        }
        $user->update([
            'otp' => null, 
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'OTP verified successfully.', 'user' => $user]);
  }


public function redirectToGoogle()
    {
        // Socialite is a Laravel package that simplifies OAuth authentication with third-party services
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Check if user exists
        $user = Users::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'first_name' => explode(' ', $googleUser->getName())[0],
                'last_name' => explode(' ', $googleUser->getName())[1] ?? '',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => null,
            ]);
            
        }
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['message' => 'Google login successful.', 'token' => $token]);
    }
}
