<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Platformpayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{

public function handleCallback(Request $request)
{
    $tx_ref = $request->query('tx_ref');

    if (!$tx_ref) {
        return response()->json(['error' => 'Transaction reference is missing'], 400);
    }

    $response = Http::withToken(env('CHAPA_SECRET_KEY'))
        ->get(env('CHAPA_BASE_URL') . "/transaction/verify/{$tx_ref}");

    if ($response->successful() && $response->json('data.status') === 'success') {
        // Update payment record
        $payment = Payment::where('tx_ref', $tx_ref)->first();

        if ($payment) {
            $payment->update(['payment_status' => 'paid']);

            $payment->booking->update(['status' => 'confirmed']);

            return response()->json(['sucess' => 'Payment sucessfully'], 200);
        }

        return response()->json(['error' => 'Payment not found'], 404);
    }

    return response()->json(['error' => 'Payment verification failed'], 400);
}

public function listingCallback(Request $request)
{
    $tx_ref = $request->get('tx_ref');

    $payment = Platformpayment::where('tx_ref', $tx_ref)->first();
    if (!$payment) return response()->json(['error' => 'Listing payment not found'], 404);

    $verify = Http::withToken(env('CHAPA_SECRET_KEY'))
        ->get(env('CHAPA_BASE_URL') . '/transaction/verify/' . $tx_ref);

    if ($verify->successful() && $verify['data']['status'] === 'success') {
        $payment->update(['payment_status' => 'successful']);
        $payment->car()->update(['status' => 'available']); // Now the car is listed

        return response()->json(['message' => 'Listing payment confirmed']);
    }

    return response()->json(['error' => 'Payment verification failed'], 400);
}


}
