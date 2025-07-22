<?php

namespace App\Services;

use Laravel\Socialite\Facades\Socialite;
use App\Models\Users;
use App\Models\AuthCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SSOService
{
    public function redirectToGoogle($platform = null)
    {
        $redirectUrl = Socialite::driver('google')
            ->stateless()
            ->with(['state' => $platform])
            ->redirect()
            ->getTargetUrl();

        return $redirectUrl;
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = Users::where('email', $googleUser->getEmail())->first();
        $fullName = $googleUser->getName();
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        if (!$user) {
            $user = Users::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $googleUser->getEmail(),
                'hash_password' => Hash::make(Str::random(16)),
                'phone' => '',
                'role' => 'User',
                'digital_id' => '',
                'driver_licence' => '',
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

        return [
            'redirect_url' => $redirectTo,
            'user' => $user,
            'code' => $code
        ];
    }

    public function exchangeCode($code)
    {
        $authCode = AuthCode::where('code', $code)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$authCode) {
            throw new \Exception('Invalid or expired code', 400);
        }

        $user = Users::find($authCode->user_id);

        $token = $user->createToken('google-login-token')->plainTextToken;

        // Delete the used auth code
        $authCode->delete();

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
