<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Platformpayment;
use Illuminate\Http\Request;
use App\Models\Car;
use App\Models\Home;


/**
 * @OA\Tag(
 *     name="Payment Processing",
 *     description="Payment methods and transaction handling endpoints"
 * )
 */
class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/chapa/callback",
     *     summary="Handle Chapa payment callback for bookings",
     *     tags={"Payment Processing"},
     *     description="Webhook endpoint to handle payment verification callbacks from Chapa for booking payments",
     *     @OA\Parameter(
     *         name="tx_ref",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Transaction reference from Chapa",
     *         example="TX-abc123"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment verified and processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Payment successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment verification failed or missing transaction reference",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Transaction reference is missing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Payment not found")
     *         )
     *     )
     * )
     */
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
    $tx_ref = $request->query('tx_ref');

    $payment = Platformpayment::where('tx_ref', $tx_ref)->first();
    if (!$payment) return response()->json(['error' => 'Listing payment not found'], 404);

    $verify = Http::withToken(env('CHAPA_SECRET_KEY'))
        ->get(env('CHAPA_BASE_URL') . '/transaction/verify/' . $tx_ref);

    if ($verify->successful() && $verify->json('data.status') === 'success') {
        $payment->update(['payment_status' => 'successful']);
       
        
        // Update item status (car or home)
        $item = $payment->item;
        if ($item instanceof Car || $item instanceof Home) {
            $item->update(['status' => 'available']);
        }

        

        return response()->json(['message' => 'Listing payment confirmed']);
    }

    return response()->json(['error' => 'Payment verification failed'], 400);
}

public function handleRedirect(Request $request)
    {
        $txRef = $request->query('tx_ref');

        if (!$txRef) {
            return response('Invalid request', 400);
        }

        $payment = PlatformPayment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            return response('Payment not found', 404);
        }


        // Redirect to your mobile app
        return redirect()->away('myapp://platformpayment?tx_ref=' . $txRef);
    }

    public function handleRedirectForBooking(Request $request)
    {
        $txRef = $request->query('tx_ref');

        if (!$txRef) {
            return response('Invalid request', 400);
        }

        $payment = Payment::where('tx_ref', $txRef)->first();

        if (!$payment) {
            return response('Payment not found', 404);
        }


        // Redirect to your mobile app
        return redirect()->away('myapp://payment-return?tx_ref=' . $txRef);
    }



}

