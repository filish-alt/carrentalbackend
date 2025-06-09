<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Users;
use App\Models\Car;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class BookingController extends Controller
{
      // Store a new booking
    public function store(Request $request)
      {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after:pickup_date',
            'total_price' => 'required|numeric|min:1',
        ]);
       
          $data['user_id'] = auth()->id();
          $data['status'] = 'pending';
          // Check if the car is available
          $car = Car::findOrFail($data['car_id']);
      
          if (!$car) {
              return response()->json(['error' => 'Car not found'], 404);
          }
      
          if ($car->status !== 'available') {
              return response()->json(['error' => 'Car is not available for booking'], 400);
          }

            // Prevent booking own car
          if ($car->owner_id === auth()->id()) {
            return $this->errorResponse("You Can't Book Your Own car", null, 403);
           }
            
      
          $overlappingBooking = Booking :: where('car_id',$car->id)
             ->where(function ($query) use ($data){
                $query->whereBetween('pickup_date', [$data['pickup_date'],$data['return_date']])
                       ->orWhereBetween('return_date', [$data['pickup_date'],$data['return_date']])
                        ->orWhere(function ($q) use ($data) {
                            $q->where('pickup_date', '<=', $data['pickup_date'])
                                ->where('return_date', '>=', $data['return_date']);
                            });
                }) ->exists(); 

      if ($overlappingBooking) {
        return response()->json(['error' => 'Car is already booked for the selected date range'], 409);
          }

          // Create the booking if car is available
          $booking = Booking::create($data);
          $tx_ref = 'TX-' . uniqid();
          
          // Create a payment record with 
                $booking->payment()->create([
                    'booking_id' => $booking->id,
                    'amount' => $data['total_price'],
                    'payment_status' => 'pending', 
                    'payment_method' => 'chapa',
                    'transaction_date' => now(),
                    'tx_ref' => $tx_ref,
                ]);

    $chapaData = [
        'amount' => $booking->total_price,
        'currency' => 'ETB',
        'email' => auth()->user()->email,
        'first_name' => auth()->user()->first_name,
        'phone_number' => auth()->user()->phone,
        'tx_ref' => $tx_ref,
        'callback_url' =>  url('/api/chapa/callback'),
        //'return_url' =>  ,
        'customization' => [
            'title' => 'Booking Payment',
            'description' => 'Payment for car rental',
        ],
    ];
  Log::info('Chapa Response', $chapaData);
     $response = Http::withToken(env('CHAPA_SECRET_KEY'))
        ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);
        $data = $response->json();
        if ($response->successful() && isset($response['data']['checkout_url'])) {
            return response()->json([
                'message' => 'Booking created. Redirect to Chapa.',
                'checkout_url' => $data['data']['checkout_url'], 
            ]);

        }
     return response()->json(['error' => 'Unable to redirect to payment'], 500);
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

     
// ADMIN: List all bookings
public function adminIndex()
{
    $bookings = Booking::with(['car', 'user'])->get();
    return response()->json([
        'message' => 'All bookings fetched successfully.',
        'bookings' => $bookings
    ]);
}

// ADMIN: Show specific booking by ID
public function adminShow($id)
{
    $booking = Booking::with(['car', 'user'])->find($id);

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
    $booking = Booking::find($id);

    if (!$booking) {
        return response()->json(['message' => 'Booking not found.'], 404);
    }

    if ($booking->status !== 'pending') {
        return response()->json(['error' => 'Only pending bookings can be cancelled.'], 400);
    }

    $booking->update(['status' => 'cancelled']);

    return response()->json(['message' => 'Booking cancelled by admin.']);
}

}
