
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\VehicleInspectionController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\PasswordResetController;
use Laravel\Socialite\Facades\Socialite;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-phone-otp', [AuthController::class, 'verifyPhoneOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/google', [SSOController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SSOController::class, 'handleGoogleCallback']);

Route::post('/send-reset-code', [PasswordResetController::class, 'sendResetCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::apiResource('/cars', CarController::class);
    Route::apiResource('/vehicle-inspections', VehicleInspectionController::class);
    Route::apiResource('/vehicle-categories', VehicleCategoryController::class);
});
