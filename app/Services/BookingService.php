<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BookingService
{
    public function createBooking(array $data, $request)
    {
        // Check if the car is available
        $car = Car::findOrFail($data['car_id']);
        
        if (!$car) {
            throw new \Exception('Car not found', 404);
        }
        
        if ($car->status !== 'available') {
            throw new \Exception('Car is not available for booking', 400);
        }

        // Prevent booking own car
        if ($car->owner_id === auth()->id()) {
            throw new \Exception("You Can't Book Your Own car", 403);
        }
        
        // Check for overlapping bookings
        $overlappingBooking = Booking::where('car_id', $car->id)
            ->where(function ($query) use ($data) {
                $query->whereBetween('pickup_date', [$data['pickup_date'], $data['return_date']])
                      ->orWhereBetween('return_date', [$data['pickup_date'], $data['return_date']])
                      ->orWhere(function ($q) use ($data) {
                          $q->where('pickup_date', '<=', $data['pickup_date'])
                            ->where('return_date', '>=', $data['return_date']);
                      });
            })->exists();

        if ($overlappingBooking) {
            throw new \Exception('Car is already booked for the selected date range', 409);
        }

        // Set user_id and status
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        // Create the booking
        $booking = Booking::create($data);
        $tx_ref = 'TX-' . uniqid();
        
        // Create payment record
        $booking->payment()->create([
            'booking_id' => $booking->id,
            'amount' => $data['total_price'],
            'payment_status' => 'pending', 
            'payment_method' => 'chapa',
            'transaction_date' => now(),
            'tx_ref' => $tx_ref,
        ]);

        // Handle payment processing
        return $this->processPayment($booking, $tx_ref, $request);
    }

    private function processPayment($booking, $tx_ref, $request)
    {
        $returnUrl = $request->header('Platform') === 'mobile'
            ? url('/api/redirect/booking-payment') . '?tx_ref=' . $tx_ref
            : env('FRONTEND_RETURN_URL') . '?tx_ref=' . $tx_ref;

        Log::info($returnUrl);
        
        $chapaData = [
            'amount' => $booking->total_price,
            'currency' => 'ETB',
            'email' => auth()->user()->email,
            'first_name' => auth()->user()->first_name,
            'phone_number' => auth()->user()->phone,
            'tx_ref' => $tx_ref,
            'callback_url' => url('/api/chapa/callback'),
            'return_url' => $returnUrl,
            'customization' => [
                'title' => 'Booking Payment',
                'description' => 'Payment for car rental',
            ],
        ];

        Log::info('Chapa Response', $chapaData);
        
        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);
            
        $data = $response->json();
        
        if ($response->successful() && isset($data['data']['checkout_url'])) {
            return [
                'message' => 'Booking created. Redirect to Chapa.',
                'checkout_url' => $data['data']['checkout_url'],
            ];
        }

        throw new \Exception('Unable to redirect to payment', 500);
    }

    public function getUserBookings()
    {
        return Booking::where('user_id', Auth::id())->with('car')->get();
    }

    public function getBooking($id)
    {
        $booking = Booking::with('car')->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        return $booking;
    }

    public function cancelBooking($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Only pending bookings can be cancelled', 400);
        }

        $booking->update(['status' => 'cancelled']);
        
        return ['message' => 'Booking cancelled'];
    }

    // Admin methods
    public function getAllBookings()
    {
        return Booking::with(['car', 'user'])->get();
    }

    public function getBookingForAdmin($id)
    {
        return Booking::with(['car', 'user'])->find($id);
    }

    public function adminCancelBooking($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            throw new \Exception('Booking not found', 404);
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Only pending bookings can be cancelled', 400);
        }

        $booking->update(['status' => 'cancelled']);
        
        return ['message' => 'Booking cancelled by admin'];
    }
}
