
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
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\LandingContentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\HomereviewController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\VerificationController;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminRegistrationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\HomeBookingController;
use App\Http\Controllers\ListingFeeController;

use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuditController;
use Illuminate\Http\Request;





Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-phone-otp', [AuthController::class, 'verifyPhoneOtp']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::get('/auth/google', [SSOController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SSOController::class, 'handleGoogleCallback']);
Route::post('/auth/exchange-code', [SSOController::class, 'exchangeCode']);

Route::get('/redirect/payment', [PaymentController::class, 'handleRedirect']);
Route::get('/redirect/booking-payment', [PaymentController::class, 'handleRedirectForBooking']);

Route::get('/users/{id}', [UserController::class, 'getUserById']);
Route::post('/resendOtp', [PasswordResetController::class, 'resendOtp'])->middleware('throttle:resendOtp');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/resendOtpPhone', [PasswordResetController::class, 'resendOtpWithIdentifier']);
Route::post('/send-verification-code', [PasswordResetController::class, 'forgotPassword']);
Route::post('/verifyEmailOtp', [AuthController::class, 'verifyEmailOtp']);
Route::post('/verify-2fa', [UserController::class, 'verify2FA']);

Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/cars/{car}/reviews', [ReviewController::class, 'reviewsForCar']);

Route::get('/home/reviews', [HomereviewController::class, 'index']);
Route::get('/home/{home}/reviews', [HomereviewController::class, 'reviewsForHome']);

Route::get('/chapa/callback', [PaymentController::class, 'handleCallback'])->name('api.chapa.callback');
Route::get('/chapa/listing-callback', [PaymentController::class, 'listingCallback']);

Route::get('/chapa/sale-callback', [PaymentController::class, 'handleSaleCallback']);
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

//
Route::get('/cars/search', [CarController::class, 'search']);
Route::get('cars', [CarController::class, 'index']); 
Route::get('cars/{car}', [CarController::class, 'show']);
Route::get('/cars/{car}/images', [CarController::class, 'getCarImages']);


// Homes routes in detail
Route::get('/homes', [HomeController::class, 'index']);           // List all homes
Route::get('/homes/{id}', [HomeController::class, 'show']);       // Get home by ID

Route::get('homes/{id}/images', [HomeController::class, 'getHomeImages']);
Route::get('search/homes', [HomeController::class, 'search']);



Route::middleware('auth:sanctum')->group(function () {

    Route::post('/adminregister', [AdminRegistrationController::class, 'register']);
    Route::get('/getalladmin', [AdminRegistrationController::class, 'getAllAdmin']);

    Route::post('/homes', [HomeController::class, 'store']); 
    Route::put('/homes/{id}', [HomeController::class, 'update']);     // Update home by ID 
    Route::delete('/homes/{id}', [HomeController::class, 'destroy']);

    Route::get('/listing-fees', [ListingFeeController::class, 'index']);
    Route::get('/listing-fees/{id}', [ListingFeeController::class, 'show']);

    Route::middleware(['App\Http\Middleware\AdminMiddleware'])->group(function () {
        Route::get('/users/by-type', [AdminController::class, 'usersByType']);
        Route::get('/permissions', [RoleController::class, 'index']);
        Route::post('/createrole', [RoleController::class, 'createRole']);
        Route::post('/assignRole', [RoleController::class, 'assignRole']);
       
        Route::post('/listing-fees', [ListingFeeController::class, 'store']);
       
        Route::put('/listing-fees', [ListingFeeController::class, 'update']);
        Route::delete('/listing-fees', [ListingFeeController::class, 'destroy']);
        Route::get('/admin/sales', [SaleController::class, 'adminIndex']);
        Route::post('homes/{id}/approve', [HomeController::class, 'approveHome']);
        Route::post('homes/{id}/reject', [HomeController::class, 'rejectHome']);
        Route::post('homes/{id}/block', [HomeController::class, 'blockHome']);
        Route::get('admin/audits', [AuditController::class, 'index']);
        Route::delete('admin/review/{id}', [ReviewController::class, 'destroy']);
    });
  

    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    Route::post('/user/profile-picture', [UserController::class, 'updateProfilePicture']);
    Route::patch('/user/toggle-2fa', [UserController::class, 'toggleTwoFactor']);
    Route::delete('/account', [UserController::class, 'deleteAccount']);

    Route::get('/admin/deleted-users', [AdminController::class, 'listDeletedUsers']);
    Route::delete('/admin/delete-user/{id}', [AdminController::class, 'forceDeleteUser']);
    Route::patch('/admin/users/{id}/verify', [AdminController::class, 'verifyUser']);
    Route::get('/admin/users/status/{status}', [AdminController::class, 'getUsersByStatus']);

    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
    Route::get('/my-car-reviews', [ReviewController::class, 'reviewsForMyCars']);

    Route::post('/home/reviews', [HomereviewController::class, 'store']);
    Route::get('/home/my-reviews', [HomereviewController::class, 'myhomeReviews']);
    Route::get('/home/my-home-reviews', [HomereviewController::class, 'reviewsForMyHomes']);

    
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
    Route::get('/my-home-reviews', [ReviewController::class, 'reviewsForMyCars']);
    
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales', [SaleController::class, 'index']);
    Route::get('/sales/{id}', [SaleController::class, 'show']);

    Route::post('/booking', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);            // List user's bookings
    Route::get('/bookings/{id}', [BookingController::class, 'show']);        // View specific booking
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']); // Cancel user's own booking
    Route::get('/bookings/admin', [BookingController::class, 'adminIndex']);            // List all bookings
    Route::get('/bookings/admin/{id}', [BookingController::class, 'adminShow']);        // View booking details
    Route::patch('/bookings/admin/{id}/cancel', [BookingController::class, 'adminCancel']); // Cancel booking

    Route::prefix('home-bookings')->controller(HomeBookingController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/cancel', 'cancel');
    });

    Route::prefix('admin/home-bookings')->controller(HomeBookingController::class)->group(function () {
        Route::get('/', 'adminIndex');
        Route::get('/{id}', 'adminShow');
        Route::put('/{id}/cancel', 'adminCancel');
    });

    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::post('/users/{id}', [UserController::class, 'updateUser']);
    Route::patch('/users/{id}/ban', [UserController::class, 'banUser']);     // Ban a user
    Route::patch('/users/{id}/unban', [UserController::class, 'unbanUser']); // Unban a user
    Route::delete('/users/{id}', [UserController::class, 'deleteUser']);     // Delete a user


    Route::post('cars', [CarController::class, 'store']);
    Route::get('mycars', [CarController::class, 'myCars']);
    Route::put('cars/{car}', [CarController::class, 'update']);
    Route::delete('cars/{car}', [CarController::class, 'destroy']);
    Route::patch('/cars/{id}/approve', [CarController::class, 'approveCar']); // Approve car listing
    Route::patch('/cars/{id}/reject', [CarController::class, 'rejectCar']);   // Reject car listing
    Route::patch('/cars/{id}/block', [CarController::class, 'blockCar']);     // Block car
    Route::patch('/cars/{id}/status', [CarController::class, 'CarStatus']); 

    Route::apiResource('/vehicle-inspections', VehicleInspectionController::class);
    Route::apiResource('/vehicle-categories', VehicleCategoryController::class);

    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
    Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);
    Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
    Route::get('/users/{userId}/payment-methods', [PaymentMethodController::class, 'getByUserId']);

    Route::get('/users/{id}/notifications', [NotificationController::class, 'getByUserId']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::put('/user/notifications/preferences', [NotificationController::class, 'updatePreferences']);

    Route::get('/user/verification/status', [VerificationController::class, 'status']);
    Route::post('/user/verification/id', [VerificationController::class, 'submitId']);
    Route::post('/user/verification/payment', [VerificationController::class, 'verifyPayment']);
    Route::post('/user/verification/car', [VerificationController::class, 'submitCar']);
    Route::post('/user/verification/send-otp', [VerificationController::class, 'sendOtp']);
    Route::post('/user/verification/phone', [VerificationController::class, 'verifyPhone']);
    Route::post('/user/verification/send-email-token', [VerificationController::class, 'sendEmailVerification']);
    Route::post('/user/verification/email', [VerificationController::class, 'verifyEmail']);
    Route::post('/user/verification/payment', [VerificationController::class, 'verifyPayment']);
    Route::put('/user/verification/status', [VerificationController::class, 'updateStatus']);

    Route::get('/verifications', [VerificationController::class, 'listPending']);        // List all pending verifications
    Route::get('/verifications/{id}', [VerificationController::class, 'showVerification']); // View single verification
    Route::patch('/verifications/{id}/approve', [VerificationController::class, 'approve']); // Approve
    Route::patch('/verifications/{id}/reject', [VerificationController::class, 'reject']);   // Reject
});
