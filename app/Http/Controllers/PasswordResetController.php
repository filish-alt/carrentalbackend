<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PasswordResetService;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    protected $passwordResetService;

    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }
   public function resendOtp(Request $request)
   {
       $request->validate([
           'user_id' => 'required|exists:users,id',
       ]);

       try {
           $result = $this->passwordResetService->resendOtp($request->user_id);
           return response()->json($result);
       } catch (\Exception $e) {
           return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
       }
   }

    public function resendOtpWithIdentifier(Request $request)
    {
        $request->validate([
            'phone' => 'required',
        ]);

        try {
            $result = $this->passwordResetService->resendOtpWithIdentifier($request->phone);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
        ]);

        try {
            $result = $this->passwordResetService->forgotPassword($request->identifier);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'code' => 'required|digits:6',
            'password' => 'required|confirmed|min:6',
        ]);

        try {
            $result = $this->passwordResetService->resetPassword(
                $request->identifier, 
                $request->code, 
                $request->password
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function resetPasswordWithOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $result = $this->passwordResetService->resetPasswordWithOTP(
                $request->email, 
                $request->otp, 
                $request->password
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

}
