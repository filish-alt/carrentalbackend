<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Users;
use App\Models\Car;
class BookingController extends Controller
{
      // Store a new booking
      public function store(Request $request)
      {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:start_date',
            'total_price' => 'required|numeric|min:1',
            'status' => 'required',
        ]);
       
          $data['user_id'] = auth()->id();
      
          // Check if the car is available
          $car = Car::find($data['car_id']);
      
          if (!$car) {
              return response()->json(['error' => 'Car not found'], 404);
          }
      
          if ($car->status !== 'available') {
              return response()->json(['error' => 'Car is not available for booking'], 400);
          }
      
          // Create the booking if car is available
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
