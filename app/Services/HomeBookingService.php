<?php

namespace App\Services;

use App\Models\HomeBooking;
use App\Models\Home;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HomeBookingService
{
    public function createBooking(array $data, $request)
    {
        $home = Home::findOrFail($data['home_id']);

        // Prevent booking own property
        if ($home->owner_id === auth()->id()) {
            throw new \Exception("You can't book your own home", 403);
        }

        // Ensure home listing type matches booking type
        if (
            ($data['booking_type'] === 'rent' && $home->listing_type !== 'rent') ||
            ($data['booking_type'] === 'buy' && $home->listing_type !== 'sale')
        ) {
            throw new \Exception('This home is not available for the selected booking type', 400);
        }

        // Check for overlapping rental dates
        if ($data['booking_type'] === 'rent') {
            $overlap = HomeBooking::where('home_id', $home->id)
                ->where('booking_type', 'rent')
                ->where(function ($query) use ($data) {
                    $query->whereBetween('check_in_date', [$data['check_in_date'], $data['check_out_date']])
                          ->orWhereBetween('check_out_date', [$data['check_in_date'], $data['check_out_date']])
                          ->orWhere(function ($q) use ($data) {
                              $q->where('check_in_date', '<=', $data['check_in_date'])
                                ->where('check_out_date', '>=', $data['check_out_date']);
                          });
                })->exists();

            if ($overlap) {
                throw new \Exception('Home is already rented for the selected date range', 409);
            }
        }

        // Set user_id and status
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        // Create the booking
        $booking = HomeBooking::create($data);
        $tx_ref = 'TX-HOME-' . uniqid();
        
        Log::info('Creating Home Booking', [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'home_id' => $booking->home_id,
            'booking_type' => $booking->booking_type,
            'check_in_date' => $booking->check_in_date,
            'check_out_date' => $booking->check_out_date,
            'total_price' => $booking->total_price,
            'guests' => $booking->guests,
        ]);

        // Create associated payment
        $booking->payment()->create([
            'home_booking_id' => $booking->id,
            'amount' => $data['total_price'],
            'payment_status' => 'pending',
            'payment_method' => 'chapa',
            'transaction_date' => now(),
            'tx_ref' => $tx_ref,
        ]);

        Log::info('Home Booking Created', [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'home_id' => $booking->home_id,
            'booking_type' => $booking->booking_type,
            'check_in_date' => $booking->check_in_date,
            'check_out_date' => $booking->check_out_date,
            'total_price' => $booking->total_price,
            'guests' => $booking->guests,
        ]);

        // Handle payment processing
        return $this->processPayment($booking, $tx_ref, $request, $data);
    }

    private function processPayment($booking, $tx_ref, $request, $data)
    {
        // Prepare Chapa payment data
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
                'title' => 'Home Booking Payment',
                'description' => 'Payment for home ' . $data['booking_type'],
            ],
        ];

        Log::info('Chapa Request', $chapaData);

        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);

        $responseData = $response->json();

        if ($response->successful() && isset($responseData['data']['checkout_url'])) {
            return [
                'message' => 'Booking created. Redirect to Chapa.',
                'checkout_url' => $responseData['data']['checkout_url'],
            ];
        }

        return [
            'message' => 'Booking created successfully. Proceed to payment.',
            'booking' => $booking
        ];
    }

    public function getUserBookings()
    {
        return HomeBooking::where('user_id', Auth::id())->with('home')->get();
    }

    public function getBooking($id)
    {
        $booking = HomeBooking::with('home')->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            throw new \Exception('Unauthorized', 403);
        }

        return $booking;
    }

    public function cancelBooking($id)
    {
        $booking = HomeBooking::findOrFail($id);

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
        return HomeBooking::with(['home', 'user'])->get();
    }

    public function getBookingForAdmin($id)
    {
        return HomeBooking::with(['home', 'user'])->find($id);
    }

    public function adminCancelBooking($id)
    {
        $booking = HomeBooking::find($id);

        if (!$booking) {
            throw new \Exception('Booking not found.', 404);
        }

        if ($booking->status !== 'pending') {
            throw new \Exception('Only pending bookings can be cancelled.', 400);
        }

        $booking->update(['status' => 'cancelled']);
        
        return ['message' => 'Booking cancelled by admin.'];
    }
}
