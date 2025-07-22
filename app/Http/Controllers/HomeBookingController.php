<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HomeBookingService;
use Illuminate\Support\Facades\Auth;

class HomeBookingController extends Controller
{
    protected $homeBookingService;

    public function __construct(HomeBookingService $homeBookingService)
    {
        $this->homeBookingService = $homeBookingService;
    }
    // Store a new home booking (Rent or Buy)
    public function store(Request $request)
    {
        $data = $request->validate([
            'home_id' => 'required|exists:homes,id',
            'booking_type' => 'required|in:rent,buy',
            'check_in_date' => 'nullable|date|after_or_equal:today|required_if:booking_type,rent',
            'check_out_date' => 'nullable|date|after:check_in_date|required_if:booking_type,rent',
            'purchase_date' => 'nullable|date|after_or_equal:today|required_if:booking_type,buy',
            'total_price' => 'required|numeric|min:1',
            'guests' => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->homeBookingService->createBooking($data, $request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // Get user's home bookings
    public function index()
    {
        $bookings = $this->homeBookingService->getUserBookings();
        return response()->json($bookings);
    }

    // Show single booking
    public function show($id)
    {
        try {
            $booking = $this->homeBookingService->getBooking($id);
            return response()->json($booking);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // Cancel a pending home booking
    public function cancel($id)
    {
        try {
            $result = $this->homeBookingService->cancelBooking($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ADMIN: List all home bookings
    public function adminIndex()
    {
        $bookings = $this->homeBookingService->getAllBookings();
        return response()->json([
            'message' => 'All home bookings fetched successfully.',
            'bookings' => $bookings
        ]);
    }

    // ADMIN: View single booking
    public function adminShow($id)
    {
        $booking = $this->homeBookingService->getBookingForAdmin($id);

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
            $result = $this->homeBookingService->adminCancelBooking($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
