<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;


/**
 * @OA\Tag(
 *     name="Payment Processing",
 *     description="Payment methods and transaction handling endpoints"
 * )
 */
class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

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
        try {
            $result = $this->paymentService->handleCallback($request->query('tx_ref'));
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

public function listingCallback(Request $request)
    {
        try {
            $result = $this->paymentService->handleListingCallback($request->query('tx_ref'));
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function handleRedirect(Request $request)
    {
        try {
            $redirectUrl = $this->paymentService->handleRedirect($request->query('tx_ref'));
            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function handleRedirectForBooking(Request $request)
    {
        try {
            $redirectUrl = $this->paymentService->handleBookingRedirect($request->query('tx_ref'));
            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function verifyPayment($tx_ref)
    {
        try {
            $result = $this->paymentService->verifyPayment($tx_ref);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function getPaymentStatus($tx_ref)
    {
        try {
            $result = $this->paymentService->getPaymentStatus($tx_ref);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }



}

