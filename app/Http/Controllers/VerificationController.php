<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\PaymentMethod;


class VerificationController extends Controller
{
    // Fetch verification status
    public function status()
    {
        $verification = UserVerification::firstOrCreate(['user_id' => Auth::id()]);
        return response()->json($verification);
    }

    // Submit ID verification document
    public function submitId(Request $request)
    {
        $request->validate([
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->file('id_document')->store('id_documents', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => Auth::id()],
            ['id_document' => $path, 'id_verified' => false]
        );

        return response()->json(['message' => 'ID document uploaded successfully.', 'data' => $verification]);
    }

    // Verify phone number using OTP
    public function verifyPhone(Request $request)
    {
        $request->validate(['otp' => 'required|string']);

        $user = Auth::user();

        if ($user->otp !== $request->otp || now()->gt($user->otp_expires_at)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 400);
        }

        $user->update(['otp' => null, 'otp_expires_at' => null]);

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['phone_verified' => true]
        );

        return response()->json(['message' => 'Phone number successfully verified.', 'data' => $verification]);
    }

    // Send OTP for phone verification (helper method)
    public function sendOtp()
    {
        $user = Auth::user();
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        // Here you'd send the OTP via SMS provider API (Twilio, Nexmo, etc.)
        // For testing, simply returning OTP:
        return response()->json(['message' => 'OTP sent successfully.', 'otp' => $otp]);
    }

    // Verify email using token
    public function verifyEmail(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $user = Auth::user();

        if ($user->email_verification_token !== $request->token) {
            return response()->json(['message' => 'Invalid token provided.'], 400);
        }

        $user->update(['email_verification_token' => null, 'email_verified_at' => now()]);

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['email_verified' => true]
        );

        return response()->json(['message' => 'Email successfully verified.', 'data' => $verification]);
    }

    // Send Email verification token (helper method)
    public function sendEmailVerification()
    {
        $user = Auth::user();
        $token = Str::random(32);
        $user->update(['email_verification_token' => $token]);

        Mail::raw("Use this token to verify your email: {$token}", function ($message) use ($user) {
            $message->to($user->email)->subject('Bisrat Tech: Verify Your Email');
        });

        return response()->json(['message' => 'Email verification token sent successfully.']);
    }

    
    // Verify payment method
    public function verifyPayment(Request $request)
    {
        $request->validate(['payment_method_id' => 'required|exists:payment_methods,id']);

        $user = Auth::user();
        $paymentMethod = PaymentMethod::where('user_id', $user->id)
                                      ->where('id', $request->payment_method_id)
                                      ->first();

        if (!$paymentMethod) {
            return response()->json(['message' => 'Payment method not found or does not belong to user.'], 404);
        }

        // Real-world logic: e.g., perform a small authorization transaction or API verification.
        // For simplicity, we assume verification success:
        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            ['payment_verified' => true]
        );

        return response()->json(['message' => 'Payment method successfully verified.', 'data' => $verification]);
    }

    // Submit car documentation
    public function submitCar(Request $request)
    {
        $request->validate([
            'car_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->file('car_document')->store('car_documents', 'public');

        $verification = UserVerification::updateOrCreate(
            ['user_id' => Auth::id()],
            ['car_document' => $path, 'car_verified' => false]
        );

        return response()->json(['message' => 'Car documentation uploaded successfully.', 'data' => $verification]);
    }

    // Manually update a specific verification status
    public function updateStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'field' => 'required|string|in:id_verified,phone_verified,email_verified,payment_verified,car_verified',
            'value' => 'required|boolean',
        ]);

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $request->user_id],
            [$request->field => $request->value]
        );

        return response()->json([
            'message' => "Verification field '{$request->field}' updated to {$request->value}.",
            'data' => $verification,
        ]);
    }
// ADMIN: List all pending verifications
public function listPending()
{
    $verifications = UserVerification::where(function ($query) {
        $query->where('id_verified', false)
              ->orWhere('phone_verified', false)
              ->orWhere('email_verified', false)
              ->orWhere('payment_verified', false)
              ->orWhere('car_verified', false);
    })->with('user')->get();

    return response()->json(['verifications' => $verifications]);
}

// ADMIN: View verification by ID
public function showVerification($id)
{
    $verification = UserVerification::with('user')->find($id);

    if (!$verification) {
        return response()->json(['message' => 'Verification record not found.'], 404);
    }

    return response()->json(['verification' => $verification]);
}

// ADMIN: Approve all user documents
public function approve($id)
{
    $verification = UserVerification::find($id);

    if (!$verification) {
        return response()->json(['message' => 'Verification record not found.'], 404);
    }

    $verification->update([
        'id_verified' => true,
        'phone_verified' => true,
        'email_verified' => true,
        'payment_verified' => true,
        'car_verified' => true
    ]);

    return response()->json(['message' => 'All documents approved successfully.']);
}

// ADMIN: Reject all documents (soft reset)
public function reject($id)
{
    $verification = UserVerification::find($id);

    if (!$verification) {
        return response()->json(['message' => 'Verification record not found.'], 404);
    }

    $verification->update([
        'id_verified' => false,
        'phone_verified' => false,
        'email_verified' => false,
        'payment_verified' => false,
        'car_verified' => false
    ]);

    return response()->json(['message' => 'All documents rejected.']);
}

}
