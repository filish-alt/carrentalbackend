<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BookingService;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:pickup_date',
            'total_price' => 'required|numeric|min:1',
        ]);

        try {
            $result = $this->bookingService->createBooking($data, $request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
      
    // List all bookings for the authenticated user
    public function index()
    {
        $bookings = $this->bookingService->getUserBookings();
        return response()->json($bookings);
    }

    // Show a specific booking
    public function show($id)
    {
        try {
            $booking = $this->bookingService->getBooking($id);
            return response()->json($booking);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

     // Cancel a booking
     public function cancel($id)
     {
         try {
             $result = $this->bookingService->cancelBooking($id);
             return response()->json($result);
         } catch (\Exception $e) {
             return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
         }
     }

     
// ADMIN: List all bookings
public function adminIndex()
{
    $bookings = $this->bookingService->getAllBookings();
    return response()->json([
        'message' => 'All bookings fetched successfully.',
        'bookings' => $bookings
    ]);
}

// ADMIN: Show specific booking by ID
public function adminShow($id)
{
    $booking = $this->bookingService->getBookingForAdmin($id);

    if (!$booking) {
        return response()->json(['message' => 'Booking not found.'], 404);
    }

    return response()->json([
        'message' => 'Booking details fetched successfully.',
        'booking' => $booking
    ]);
}

// ADMIN: Cancel booking
public function adminCancel($id)
{
    try {
        $result = $this->bookingService->adminCancelBooking($id);
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
    }
}

}
