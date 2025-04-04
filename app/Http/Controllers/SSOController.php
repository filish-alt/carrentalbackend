<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SSOController extends Controller
{

        
    public function redirectToGoogle()
    {
       
        return  Socialite::driver('google')->stateless()->redirect();

    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
    
            $user = Users::where('email', $googleUser->getEmail())->first();
            $fullName = $googleUser->getName();
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? ''; 

            if (! $user) {
                $user = Users::create([
                    'first_name' => $firstName ,
                    'last_name' => $lastName,
                    'email' => $googleUser->getEmail(),
                    'hash_password' => Hash::make(Str::random(16)),
                    'phone' => '',
                    'role' => 'user',
                    'digital_id' => '',
                    'sso_id' => $googleUser->getId()

                ]);
            }
    
            // Generate Sanctum token
            $token = $user->createToken('google-login-token')->plainTextToken;
    
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}

