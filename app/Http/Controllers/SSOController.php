<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

use Carbon\Carbon;
use App\Models\AuthCode; 

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
                    'driver_liscence' => '',
                    'sso_id' => $googleUser->getId()

                ]);
            }
            
            // Generate short-lived auth code
             $code = Str::random(40);

            //$token = $user->createToken('google-login-token')->plainTextToken;
        AuthCode::create([
            'code' => $code,
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addMinutes(10), // 1-minute expiry
        ]);

        // Redirect to frontend with code only
        return redirect()->to("http://localhost:3000/sso-callback?code={$code}");
            // return response()->json([
            //     'token' => $token,
            //     'user' => $user,
            // ]);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function exchangeCode(Request $request) {

         $request->validate([
        'code' => 'required|string',
    ]);
     $authCode = AuthCode::where('code', $request->code)
        ->where('expires_at', '>', Carbon::now())
        ->first();

    if (! $authCode) {
        return response()->json(['error' => 'Invalid or expired code'], 400);
    }
       $user = Users::find($authCode->user_id);

    // Delete the code after use
    $authCode->delete();

    $token = $user->createToken('google-login-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);

    }
}

