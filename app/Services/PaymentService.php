<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Platformpayment;
use App\Models\Car;
use App\Models\Home;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function handleCallback($tx_ref)
    {
        if (!$tx_ref) {
            throw new \Exception('Transaction reference is missing', 400);
        }

        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->get(env('CHAPA_BASE_URL') . "/transaction/verify/{$tx_ref}");

        if ($response->successful() && $response->json('data.status') === 'success') {
            // Update payment record
            $payment = Payment::where('tx_ref', $tx_ref)->first();

            if ($payment) {
                $payment->update(['payment_status' => 'paid']);
                $payment->booking->update(['status' => 'confirmed']);

                return ['success' => 'Payment successfully processed'];
            }

            throw new \Exception('Payment not found', 404);
        }

        throw new \Exception('Payment verification failed', 400);
    }

    public function handleListingCallback($tx_ref)
    {
        $payment = Platformpayment::where('tx_ref', $tx_ref)->first();
        
        if (!$payment) {
            throw new \Exception('Listing payment not found', 404);
        }

        $verify = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->get(env('CHAPA_BASE_URL') . '/transaction/verify/' . $tx_ref);

        if ($verify->successful() && $verify->json('data.status') === 'success') {
            $payment->update(['payment_status' => 'successful']);
           
            // Update item status (car or home)
            $item = $payment->item;
            if ($item instanceof Car || $item instanceof Home) {
                $item->update(['status' => 'available']);
            }

            return ['message' => 'Listing payment confirmed'];
        }

        throw new \Exception('Payment verification failed', 400);
    }

    public function handleRedirect($txRef)
    {
        if (!$txRef) {
            throw new \Exception('Invalid request', 400);
        }

        $payment = Platformpayment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            throw new \Exception('Payment not found', 404);
        }

        // Return the redirect URL for the mobile app
        return 'myapp://platformpayment?tx_ref=' . $txRef;
    }

    public function handleBookingRedirect($txRef)
    {
        if (!$txRef) {
            throw new \Exception('Invalid request', 400);
        }

        $payment = Payment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            throw new \Exception('Payment not found', 404);
        }

        // Return the redirect URL for the mobile app
        return 'myapp://payment-return?tx_ref=' . $txRef;
    }

    public function verifyPayment($tx_ref)
    {
        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->get(env('CHAPA_BASE_URL') . "/transaction/verify/{$tx_ref}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to verify payment', 500);
    }

    public function getPaymentStatus($tx_ref)
    {
        // Check in both payment tables
        $bookingPayment = Payment::where('tx_ref', $tx_ref)->first();
        if ($bookingPayment) {
            return [
                'type' => 'booking',
                'status' => $bookingPayment->payment_status,
                'amount' => $bookingPayment->amount,
                'payment' => $bookingPayment
            ];
        }

        $platformPayment = Platformpayment::where('tx_ref', $tx_ref)->first();
        if ($platformPayment) {
            return [
                'type' => 'platform',
                'status' => $platformPayment->payment_status,
                'amount' => $platformPayment->amount,
                'payment' => $platformPayment
            ];
        }

        throw new \Exception('Payment not found', 404);
    }

    public function getAllPayments()
    {
        $bookingPayments = Payment::with('booking')->get();
        $platformPayments = Platformpayment::with('item')->get();

        return [
            'booking_payments' => $bookingPayments,
            'platform_payments' => $platformPayments
        ];
    }

    public function getPaymentsByStatus($status)
    {
        $bookingPayments = Payment::where('payment_status', $status)->with('booking')->get();
        $platformPayments = Platformpayment::where('payment_status', $status)->with('item')->get();

        return [
            'booking_payments' => $bookingPayments,
            'platform_payments' => $platformPayments
        ];
    }
}
