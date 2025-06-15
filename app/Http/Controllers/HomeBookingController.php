<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomeBooking;
use App\Models\Home;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Users;


class HomeBookingController extends Controller
{
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

        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        $home = Home::findOrFail($data['home_id']);

        // Prevent booking own property
        if ($home->owner_id === auth()->id()) {
            return response()->json(['error' => "You can't book your own home"], 403);
        }

        // Ensure home listing type matches booking type
        if (
            ($data['booking_type'] === 'rent' && $home->listing_type !== 'rent') ||
            ($data['booking_type'] === 'buy' && $home->listing_type !== 'sale')
        ) {
            return response()->json(['error' => 'This home is not available for the selected booking type'], 400);
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
                return response()->json(['error' => 'Home is already rented for the selected date range'], 409);
            }
        }

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

        // // Prepare Chapa payment data
        // $chapaData = [
        //     'amount' => $booking->total_price,
        //     'currency' => 'ETB',
        //     'email' => auth()->user()->email,
        //     'first_name' => auth()->user()->first_name,
        //     'phone_number' => auth()->user()->phone,
        //     'tx_ref' => $tx_ref,
        //     'callback_url' => url('/api/chapa/callback'),
        //     'customization' => [
        //         'title' => 'Home Booking Payment',
        //         'description' => 'Payment for home ' . $data['booking_type'],
        //     ],
        // ];

        // Log::info('Chapa Request', $chapaData);

        // $response = Http::withToken(env('CHAPA_SECRET_KEY'))
        //     ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);

        // $responseData = $response->json();

        // if ($response->successful() && isset($responseData['data']['checkout_url'])) {
        //     return response()->json([
        //         'message' => 'Booking created. Redirect to Chapa.',
        //         'checkout_url' => $responseData['data']['checkout_url'],
        //     ]);
        // }

    return response()->json([
        'message' => 'Booking created successfully. Proceed to payment.',
        'booking' => $booking
    ], 201);
    }

    // Get user's home bookings
    public function index()
    {
        $bookings = HomeBooking::where('user_id', Auth::id())->with('home')->get();
        return response()->json($bookings);
    }

    // Show single booking
    public function show($id)
    {
        $booking = HomeBooking::with('home')->findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($booking);
    }

    // Cancel a pending home booking
    public function cancel($id)
    {
        $booking = HomeBooking::findOrFail($id);

        if ($booking->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['error' => 'Only pending bookings can be cancelled'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled']);
    }

    // ADMIN: List all home bookings
    public function adminIndex()
    {
        $bookings = HomeBooking::with(['home', 'user'])->get();
        return response()->json([
            'message' => 'All home bookings fetched successfully.',
            'bookings' => $bookings
        ]);
    }

    // ADMIN: View single booking
    public function adminShow($id)
    {
        $booking = HomeBooking::with(['home', 'user'])->find($id);

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
        $booking = HomeBooking::find($id);

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
