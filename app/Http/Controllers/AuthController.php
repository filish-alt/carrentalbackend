<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Models\Users;
use App\Models\SuperAdmin;
use Illuminate\Validation\Rule;



use Illuminate\Support\Facades\Redis;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;



class AuthController extends Controller
{
    public function sendOtp($phone, $otp) {
        try {
            // Replace phone prefix
            if (str_starts_with($phone, '09')) {
                $phone = '2519' . substr($phone, 2);
            } elseif (str_starts_with($phone, '07')) {
                $phone = '2517' . substr($phone, 2);
            }
    
            Log::info("Attempting to send OTP to phone: {$phone}");
    
            $response = Http::asForm()->post('https://api.geezsms.com/api/v1/sms/send', [
                'token' => 'iE0L4t06lOKr3u2AmzFQ3d4nXe2DZpeC',
                'phone' => $phone,
                'msg'   => "Dear user, your OTP is: {$otp} It will expire in 5 minutes. Thank you!",
            ]);
    
            Log::info("SMS API Response:", ['response' => $response->json()]);
    
            $apiResponse = $response->json();
            if ($response->failed() || (isset($apiResponse['error']) && $apiResponse['error'])) { 
                Log::error('Failed to send OTP via SMS', [
                    'status' => $response->status(),
                    'response' => $apiResponse
                ]);
                return [
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again later.',
                    'api_response' => $apiResponse
                ];
            }
    
            // Return success response
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'api_response' => $apiResponse
            ];
    
        } catch (\Exception $e) {
            Log::error('Error occurred while sending OTP via SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'An error occurred while sending OTP. Please try again later.',
                'error' => $e->getMessage()
            ];
        }
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
            'driver_liscence'  => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'digital_id'       => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'passport'         => 'nullable|file|mimes:jpg,jpeg,png,pdf',
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

            $digitalIdPath = $request->file('digital_id')
            ? $request->file('digital_id')->move(base_path('../public_html/digital_ids'), uniqid() . '.' 
            . $request->file('digital_id')->getClientOriginalExtension())
            : null;
     
            $driverLiscencePath = $request->file('driver_liscence')
             ? $request->file('driver_liscence')->move(base_path('../public_html/driver_licences'), uniqid() . '.' 
             . $request->file('driver_liscence')->getClientOriginalExtension())
             : null;
            
              $passport = $request->file('passport')
             ? $request->file('passport')->move(base_path('../public_html/passport'), uniqid() . '.' 
             . $request->file('passport')->getClientOriginalExtension())
             : null;

             $driverLiscenceUrl = $driverLiscencePath ? url('driver_licences/' . basename($driverLiscencePath)) : null;
            $digitalIdUrl = $digitalIdPath ? url('digital_ids/' . basename($digitalIdPath)) : null;
            $passportUrl = $passport ? url('passport/' . basename($passport)) : null;
         
    $otp = rand(100000, 999999);

    $sms_response = $this->sendOtp($request->phone, $otp);
   

    // Create user
    $user = Users::create([
        'first_name'      => $request->first_name,
        'middle_name'     => $request->middle_name,
        'last_name'       => $request->last_name,
        'email'           => $request->email,
        'phone'           => $request->phone,
        'hash_password'   =>  Hash::make($request->password),
        'digital_id'      => $digitalIdPath,
        'passport'        => $passport,
        'driver_licence' => $driverLiscencePath,
        'address'         => $request->address,
        'city'            => $request->city,
        'birth_Date'      => $request->birth_date,
        'role'            => 'User',
        'status'          => 'Pending',
        'otp'             => $otp,
        'otp_expires_at'  => now()->addMinutes(5),
    ]);

    // Send OTP to email
    if ($user->email) {
        Mail::raw("Your registration OTP is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Account Verification OTP');
        });
    }
    
    // Simulate sending OTP 
    Log::info("OTP for {$user->phone}: {$otp}");
    
    return response()->json([
        'message' => 'User registered successfully.',
        'user' => [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_licence' => $driverLiscenceUrl,
            'digital_id' => $digitalIdUrl,
            'passport' => $passportUrl,
            'address' => $user->address,
            'city' => $user->city,
            'birth_date' => $user->birth_date,
            'status' => $user->status,
           ],
        'sms_response' => $sms_response
    ], 201);
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

    $user = Users::where('phone', $request->phone)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    return response()->json([
        'message' => 'Phone number verified successfully.',
        'user' => $user,
    ]);
}

public function verifyEmailOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp'   => 'required|string|size:6',
    ]);

    $user = Users::where('email', $request->email)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    return response()->json([
        'message' => 'Email verified successfully.',
        'user' => $user,
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

    $user = null;
    $userType = '';
   
    $loginInput = $request->email;
    $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);

    $adminQuery = SuperAdmin::query();
    // Check if SuperAdmin
    $admin = $isEmail
        ? $adminQuery->where('email', $loginInput)->first()
        : $adminQuery->where('phone', $loginInput)->first();

    if ($admin && Hash::check($request->password, $admin->hash_password)) {
        $user = $admin;
        $userType = 'admin';
    }

    // Check if regular User (only if no admin match)
     $userQuery = Users::query();
     $normalUser = $isEmail
        ? $userQuery->where('email', $loginInput)->first()
        : $userQuery->where('phone', $loginInput)->first();

        if ($normalUser && Hash::check($request->password, $normalUser->hash_password)) {
            $user = $normalUser;
            $userType = 'user';
        }
    

    
    if (!$user) {
        Log::warning('Invalid login credentials', ['email' => $request->email]);
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    // 2FA & OTP checks for normal users
    if ($userType === 'user') {
        if ($user->otp && $user->otp_expires_at) {
            return response()->json(['message' => 'Phone number not verified. Please enter the OTP sent to your phone.'], 403);
        }

        if ($user->two_factor_enabled) {
            $otp = rand(100000, 999999);

            // Save OTP temporarily in Redis
            //Redis::setex("2fa:{$user->id}", 300, $otp); // 5 minutes
            $user->two_factor_code = $otp;
            $user->two_factor_expires_at = now()->addMinutes(5);
            $user->save();
             // Send OTP via email and SMS
          Mail::raw("Your two factor code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Two factor Verification OTP');
          });
          
            $this->sendOtp($user->phone, $otp);
       
            Log::info("2FA code sent: {$otp}");

            return response()->json([
                'message' => 'Two-factor code sent',
                'two_factor_pending' => true,
                'user_id' => $user->id,
            ]);
        }
    }

    // Issue new token
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'role' => $user->role ?? $userType,
        'user' => $user,
        'token' => $token,
    ]);
}

public function logout(Request $request)
{
    $user = Auth::user();
    if ($user) {
        $user->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
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
    echo $user->name;
    if (!Hash::check($request->current_password, $user->hash_password)) {
        return response()->json(['message' => 'Current password is incorrect'], 403);
    }

    $user->hash_password = Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password updated successfully']);
}

}

