<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Users;
use App\Models\SuperAdmin;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
/**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Register a new user and send OTP",
 *     tags={"Authentication"},
 *     description="Register a new user account. The system will send an OTP to both phone and email for verification.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"first_name", "middle_name", "last_name", "email", "phone", "password", "password_confirmation"},
 *                 @OA\Property(property="first_name", type="string", example="John", description="User's first name"),
 *                 @OA\Property(property="middle_name", type="string", example="A.", description="User's middle name"),
 *                 @OA\Property(property="last_name", type="string", example="Doe", description="User's last name"),
 *                 @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
 *                 @OA\Property(property="phone", type="string", pattern="^(09|07)\d{8}$", example="0912345678", description="User's phone number (Ethiopian format)"),
 *                 @OA\Property(property="password", type="string", format="password", minLength=6, example="secret123", description="User's password (minimum 6 characters)"),
 *                 @OA\Property(property="password_confirmation", type="string", format="password", example="secret123", description="Password confirmation"),
 *                 @OA\Property(property="address", type="string", example="Addis Ababa", description="User's address (optional)"),
 *                 @OA\Property(property="city", type="string", example="Addis Ababa", description="User's city (optional)"),
 *                 @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01", description="User's birth date (optional)"),
 *                 @OA\Property(property="driver_liscence", type="string", format="binary", description="Driver's license file (optional, jpg/jpeg/png/pdf)"),
 *                 @OA\Property(property="digital_id", type="string", format="binary", description="Digital ID file (optional, jpg/jpeg/png/pdf)"),
 *                 @OA\Property(property="passport", type="string", format="binary", description="Passport file (optional, jpg/jpeg/png/pdf)")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="User registered successfully."),
 *             @OA\Property(property="user", ref="#/components/schemas/User")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error",
 *         @OA\JsonContent(ref="#/components/schemas/Error")
 *     )
 * )
 */
    
    public function register(Request $request) 
     { 
        Log::info('=== Incoming Request ===');
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'middle_name'        => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => [
                                    'required',
                                    'email',
                                    Rule::unique('users', 'email'),
                                    function ($attribute, $value, $fail) {
                                        if (DB::table('super_admins')->where('email', $value)->exists()) {
                                            $fail('The email has already been taken.');
                                        }
                                    },
                                ],
            'phone' => ['required', 'regex:/^(09|07)\d{8}$/', 'unique:users,phone'],
            'password'         => 'required|string|min:6|confirmed', 
            'driver_liscence'  => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp',
            'digital_id'       => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp',
            'passport'         => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp',
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

        $result = $this->authService->register($request->all(), $request);
        
        return response()->json($result, 201);
    }
        
/**
 * @OA\Post(
 *     path="/api/verify-phone-otp",
 *     summary="Verify phone OTP",
 *     tags={"Auth"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"phone", "otp"},
 *             @OA\Property(property="phone", type="string", example="0912345678"),
 *             @OA\Property(property="otp", type="string", example="123456")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Phone verified successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid or expired OTP"
 *     )
 * )
 */

public function verifyPhoneOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|regex:/^09\d{8}$/',
        'otp'   => 'required|string|size:6',
    ]);

    $result = $this->authService->verifyPhoneOtp($request->phone, $request->otp);

    if (!$result['success']) {
        return response()->json(['message' => $result['message']], 400);
    }

    return response()->json([
        'message' => $result['message'],
        'user' => $result['user'],
    ]);
}

public function verifyEmailOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|string|size:6',
    ]);

    $result = $this->authService->verifyEmailOtp($request->email, $request->otp);

    if (!$result['success']) {
        return response()->json(['message' => $result['message']], 400);
    }

    return response()->json([
        'message' => $result['message'],
        'user' => $result['user'],
    ]);
}

/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="Login user",
 *     tags={"Authentication"},
 *     description="Authenticate a user and return access token. Supports both regular users and admin users.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email", "password"},
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
 *             @OA\Property(property="password", type="string", format="password", example="secret123", description="User's password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Login successful"),
 *             @OA\Property(property="role", type="string", example="User"),
 *             @OA\Property(property="user", ref="#/components/schemas/User"),
 *             @OA\Property(property="token", type="string", example="1|abc123xyz...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Invalid credentials",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid credentials.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Account not verified or 2FA required",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Phone number not verified. Please enter the OTP sent to your phone."),
 *             @OA\Property(property="two_factor_pending", type="boolean", example=true),
 *             @OA\Property(property="user_id", type="integer", example=1)
 *         )
 *     )
 * )
 */

public function login(Request $request)
{
    $request->validate([
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    Log::info('=== Incoming Request ===');
    Log::info($request->all());

    $result = $this->authService->login($request->email, $request->password);

    if (!$result['success']) {
        if (isset($result['two_factor_pending']) && $result['two_factor_pending']) {
            return response()->json([
                'message' => $result['message'],
                'two_factor_pending' => true,
                'user_id' => $result['user_id']
            ], 403);
        }
        
        Log::warning('Invalid login credentials', ['email' => $request->email]);
        return response()->json(['message' => $result['message']], 401);
    }

    return response()->json([
        'message' => 'Login successful',
        'role' => $result['role'],
        'user' => $result['user'],
        'token' => $result['token'],
    ]);
}

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            $result = $this->authService->logout($user);
            return response()->json($result);
        }
        return response()->json(['message' => 'User not found.'], 404);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        $result = $this->authService->updatePassword($user, $request->current_password, $request->new_password);
        
        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 403);
        }
        
        return response()->json(['message' => $result['message']]);
    }

}

