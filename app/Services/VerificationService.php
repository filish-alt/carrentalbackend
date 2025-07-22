<?php

namespace App\Services;

use App\Models\UserVerification;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VerificationService
{
    public function getVerificationStatus($userId)
    {
        $verification = UserVerification::firstOrCreate(['user_id' => $userId]);
        return $verification;
    }

    public function submitIdDocument($request, $userId)
    {
        $path = $request->file('id_document')->store('id_documents', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $userId],
            ['id_document' => $path, 'id_verified' => false]
        );

        return [
            'message' => 'ID document uploaded successfully.',
            'data' => $verification
        ];
    }

    public function verifyPhone($otp, $user)
    {
        if ($user->otp !== $otp || now()->gt($user->otp_expires_at)) {
            throw new \Exception('Invalid or expired OTP.', 400);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null]);

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['phone_verified' => true]
        );

        return [
            'message' => 'Phone number successfully verified.',
            'data' => $verification
        ];
    }

    public function sendOtp($user)
    {
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        // Here you'd send the OTP via SMS provider API
        return [
            'message' => 'OTP sent successfully.',
            'otp' => $otp // For testing only
        ];
    }

    public function verifyEmail($token, $user)
    {
        if ($user->email_verification_token !== $token) {
            throw new \Exception('Invalid token provided.', 400);
        }

        $user->update(['email_verification_token' => null, 'email_verified_at' => now()]);

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['email_verified' => true]
        );

        return [
            'message' => 'Email successfully verified.',
            'data' => $verification
        ];
    }

    public function sendEmailVerification($user)
    {
        $token = Str::random(32);
        $user->update(['email_verification_token' => $token]);

        Mail::raw("Use this token to verify your email: {$token}", function ($message) use ($user) {
            $message->to($user->email)->subject('Bisrat Tech: Verify Your Email');
        });

        return ['message' => 'Email verification token sent successfully.'];
    }

    public function verifyPayment($paymentMethodId, $user)
    {
        $paymentMethod = PaymentMethod::where('user_id', $user->id)
                                      ->where('id', $paymentMethodId)
                                      ->first();

        if (!$paymentMethod) {
            throw new \Exception('Payment method not found or does not belong to user.', 404);
        }

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['payment_verified' => true]
        );

        return [
            'message' => 'Payment method successfully verified.',
            'data' => $verification
        ];
    }

    public function submitCarDocument($request, $userId)
    {
        $path = $request->file('car_document')->store('car_documents', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $userId],
            ['car_document' => $path, 'car_verified' => false]
        );

        return [
            'message' => 'Car documentation uploaded successfully.',
            'data' => $verification
        ];
    }

    public function updateVerificationStatus(array $data)
    {
        $verification = UserVerification::updateOrCreate(
            ['user_id' => $data['user_id']],
            [$data['field'] => $data['value']]
        );

        return [
            'message' => "Verification field '{$data['field']}' updated to {$data['value']}.",
            'data' => $verification,
        ];
    }

    // Admin methods
    public function listPendingVerifications()
    {
        $verifications = UserVerification::where(function ($query) {
            $query->where('id_verified', false)
                  ->orWhere('phone_verified', false)
                  ->orWhere('email_verified', false)
                  ->orWhere('payment_verified', false)
                  ->orWhere('car_verified', false);
        })->with('user')->get();

        return $verifications;
    }

    public function getVerificationById($id)
    {
        $verification = UserVerification::with('user')->find($id);

        if (!$verification) {
            throw new \Exception('Verification record not found.', 404);
        }

        return $verification;
    }

    public function approveVerification($id)
    {
        $verification = UserVerification::find($id);

        if (!$verification) {
            throw new \Exception('Verification record not found.', 404);
        }

        $verification->update([
            'id_verified' => true,
            'phone_verified' => true,
            'email_verified' => true,
            'payment_verified' => true,
            'car_verified' => true
        ]);

        return ['message' => 'All documents approved successfully.'];
    }

    public function rejectVerification($id)
    {
        $verification = UserVerification::find($id);

        if (!$verification) {
            throw new \Exception('Verification record not found.', 404);
        }

        $verification->update([
            'id_verified' => false,
            'phone_verified' => false,
            'email_verified' => false,
            'payment_verified' => false,
            'car_verified' => false
        ]);

        return ['message' => 'All documents rejected.'];
    }
}
