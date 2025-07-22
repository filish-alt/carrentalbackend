<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\VerificationService;

class VerificationController extends Controller
{
    protected $verificationService;

    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function status()
    {
        $data = $this->verificationService->getVerificationStatus(Auth::id());
        return response()->json($data);
    }

    public function submitId(Request $request)
    {
        $request->validate([
            'id_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $response = $this->verificationService->submitIdDocument($request, Auth::id());
        return response()->json($response);
    }

    public function verifyPhone(Request $request)
    {
        $request->validate(['otp' => 'required|string']);

        try {
            $response = $this->verificationService->verifyPhone($request->otp, Auth::user());
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function sendOtp()
    {
        $response = $this->verificationService->sendOtp(Auth::user());
        return response()->json($response);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        try {
            $response = $this->verificationService->verifyEmail($request->token, Auth::user());
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function sendEmailVerification()
    {
        $response = $this->verificationService->sendEmailVerification(Auth::user());
        return response()->json($response);
    }

    public function verifyPayment(Request $request)
    {
        $request->validate(['payment_method_id' => 'required|exists:payment_methods,id']);

        try {
            $response = $this->verificationService->verifyPayment($request->payment_method_id, Auth::user());
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function submitCar(Request $request)
    {
        $request->validate([
            'car_document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $response = $this->verificationService->submitCarDocument($request, Auth::id());
        return response()->json($response);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'field' => 'required|string|in:id_verified,phone_verified,email_verified,payment_verified,car_verified',
            'value' => 'required|boolean',
        ]);

        $response = $this->verificationService->updateVerificationStatus($request->only(['user_id', 'field', 'value']));
        return response()->json($response);
    }

    public function listPending()
    {
        $verifications = $this->verificationService->listPendingVerifications();
        return response()->json(['verifications' => $verifications]);
    }

    public function showVerification($id)
    {
        try {
            $verification = $this->verificationService->getVerificationById($id);
            return response()->json(['verification' => $verification]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function approve($id)
    {
        try {
            $response = $this->verificationService->approveVerification($id);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function reject($id)
    {
        try {
            $response = $this->verificationService->rejectVerification($id);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }
}
