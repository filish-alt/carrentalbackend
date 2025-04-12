
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\LandingContentController;
use Laravel\Socialite\Facades\Socialite;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-phone-otp', [AuthController::class, 'verifyPhoneOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/google', [SSOController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SSOController::class, 'handleGoogleCallback']);

Route::post('/send-reset-link', [PasswordResetController::class, 'sendResetLink']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/reset-password-with-otp', [PasswordResetController::class, 'resetPasswordWithOTP']);


// General Info
Route::post('/general-info', [LandingContentController::class, 'setGeneralInfo']);
Route::put('/general-info/{key}', [LandingContentController::class, 'updateGeneralInfo']);
Route::get('/general-info', [LandingContentController::class, 'listGeneralInfo']);
Route::get('/general-info/{key}', [LandingContentController::class, 'getGeneralInfo']);
Route::delete('/general-info/{key}', [LandingContentController::class, 'deleteGeneralInfo']);

// FAQ
Route::get('/faqs', [LandingContentController::class, 'listFaqs']);
Route::post('/faqs', [LandingContentController::class, 'addFaq']);
Route::put('/faqs/{id}', [LandingContentController::class, 'updateFaq']);
Route::delete('/faqs/{id}', [LandingContentController::class, 'deleteFaq']);

// Landing Sections
Route::get('/sections', [LandingContentController::class, 'listSections']);
Route::post('/sections', [LandingContentController::class, 'addSection']);
Route::put('/sections/{id}', [LandingContentController::class, 'updateSection']);
Route::delete('/sections/{id}', [LandingContentController::class, 'deleteSection']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::apiResource('cars', CarController::class);
    Route::apiResource('vehicle-inspections', VehicleInspectionController::class);
    Route::apiResource('vehicle-categories', VehicleCategoryController::class);
});
