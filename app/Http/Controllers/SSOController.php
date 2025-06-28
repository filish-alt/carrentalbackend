<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Users;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AuthCode; 

class SSOController extends Controller
{

     public function redirectToGoogle(Request $request)
        {
            $platform = $request->query('platform'); 

            $redirectUrl = Socialite::driver('google')
                ->stateless()
                ->with(['state' => $platform]) 
                ->redirect()
                ->getTargetUrl();

            return redirect($redirectUrl);
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
                    'role' => 'User',
                    'digital_id' => '',
                    'driver_liscence' => '',
                    'sso_id' => $googleUser->getId()

                ]);
            }
            
            // Generate short-lived auth code
             $code = Str::random(40);

        AuthCode::create([
            'code' => $code,
            'user_id' => $user->id,
            'expires_at' => Carbon::now()->addMinutes(10), 
        ]);

         // Detect if it's for mobile or web
        $state = request()->query('state');
        $isMobile = $state === 'mobile';

        Log::info('SSO redirect, platform: ' . $state);
       $redirectTo = $isMobile
            ? env('MOBILE_SSO_CALLBACK') . '?code=' . $code
            : env('WEB_SSO_CALLBACK') . '?code=' . $code;


        return redirect($redirectTo);

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

    $token = $user->createToken('google-login-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
    ]);

    }
}

