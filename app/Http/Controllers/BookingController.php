<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookingController extends Controller
{
      // Store a new booking
      public function store(StoreBookingRequest $request)
      {
          $data = $request->validated();
          $data['user_id'] = Auth::id(); // Assign to current user
  
          $booking = Booking::create($data);
  
          return response()->json([
              'message' => 'Booking created successfully!',
              'booking' => $booking
          ], 201);
      }

    // List all bookings for the authenticated user
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())->with('car')->get();
        return response()->json($bookings);
    }

    // Show a specific booking
    public function show($id)
    {
        $booking = Booking::with('car')->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($booking);
    }

     // Cancel a booking
     public function cancel($id)
     {
         $booking = Booking::findOrFail($id);
 
         if ($booking->user_id !== Auth::id()) {
             return response()->json(['error' => 'Unauthorized'], 403);
         }
 
         if ($booking->status !== 'pending') {
             return response()->json(['error' => 'Only pending bookings can be cancelled'], 400);
         }
 
         $booking->update(['status' => 'cancelled']);
 
         return response()->json(['message' => 'Booking cancelled']);
     }

}
