
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\VehicleInspectionController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\LandingContentController;
use App\Http\Controllers\ReviewController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-phone-otp', [AuthController::class, 'verifyPhoneOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/google', [SSOController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SSOController::class, 'handleGoogleCallback']);

Route::post('/send-reset-code', [PasswordResetController::class, 'sendResetCode']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);
Route::get('/reviews', [ReviewController::class, 'index']);
// Route::get('/test-gmail', function () {
//     Mail::to('filagot24s@gmail.com')->send(new TestMail());
//     return 'Test Gmail sent!';
// });

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
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::patch('/user/profile-picture', [UserController::class, 'updateProfilePicture']);
    Route::patch('/user/toggle-2fa', [UserController::class, 'toggleTwoFactor']);
    Route::delete('/account', [UserController::class, 'deleteAccount']);

    Route::get('/admin/deleted-users', [AdminController::class, 'listDeletedUsers']);
    Route::delete('/admin/delete-user/{id}', [AdminController::class, 'forceDeleteUser']);
    Route::patch('/admin/users/{id}/verify', [AdminController::class, 'verifyUser']);
    Route::get('/admin/users/status/{status}', [AdminController::class, 'getUsersByStatus']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
    Route::get('/my-car-reviews', [ReviewController::class, 'reviewsForMyCars']);

    Route::post('/booking', [BookingController::class, 'store']);

    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::put('/users/{id}', [UserController::class, 'updateUser']);
    Route::apiResource('/cars', CarController::class);
    Route::apiResource('/vehicle-inspections', VehicleInspectionController::class);
    Route::apiResource('/vehicle-categories', VehicleCategoryController::class);
});
