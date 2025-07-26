<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Car;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SaleService
{
public function createSale(array $data, $request)
{
    // Check if car exists
    $car = Car::findOrFail($data['car_id']);

    if (!$car) {
        throw new \Exception('Car not found', 404);
    }

    // Check availability
    if ($car->listing_type !== 'sell') {
        throw new \Exception('Car is not available for sale', 400);
    }


    // Prevent buying own car
    if ($car->owner_id === auth()->id()) {
        throw new \Exception("You can't buy your own car", 403);
    }

    // Check if already sold
    $alreadySold = Sale::where('car_id', $car->id)->where('status', 'paid')->exists();
    if ($alreadySold) {
        throw new \Exception('Car has already been sold', 409);
    }

    // Create sale
    $sale = Sale::create([
        'car_id' => $car->id,
        'buyer_id' => auth()->id(),
        'price' => $car->sell_price, 
        'status' => 'pending',
    ]);
  
     Log::info("Attempting to slae car : {$sale->id}");
     Log::info("Attempting to slae car : {$sale}");

    $tx_ref = 'SALE-TX-' . uniqid();

    // Create payment
    $sale->payment()->create([
    'sale_id' => $sale->id,
    'amount' => $sale->price,
    'payment_status' => 'pending',
    'payment_method' => 'chapa',
    'transaction_date' => now(),
    'tx_ref' => $tx_ref,
]);



    // Redirect/initiate chapa payment
    return $this->processSalePayment($sale, $tx_ref, $request);
}

    private function processSalePayment($sale, $tx_ref, $request)
    {
        $returnUrl = $request->header('Platform') === 'mobile'
            ? url('/api/redirect/booking-payment') . '?tx_ref=' . $tx_ref
            : env('FRONTEND_RETURN_URL') . '?tx_ref=' . $tx_ref;

        Log::info($returnUrl);
        
        $chapaData = [
            'amount' => $sale->price,
            'currency' => 'ETB',
            'email' => auth()->user()->email,
            'first_name' => auth()->user()->first_name,
            'phone_number' => auth()->user()->phone,
            'tx_ref' => $tx_ref,
            'callback_url' => url('/api/chapa/sale-callback'),
            'return_url' => $returnUrl,
            'customization' => [
                'title' => 'Sale Payment',
                'description' => 'Payment for car buy',
            ],
        ];

        Log::info('Chapa Response', $chapaData);
        
        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);
            
        $data = $response->json();
        
        if ($response->successful() && isset($data['data']['checkout_url'])) {
            return [
                'message' => 'Sales created. Redirect to Chapa.',
                'checkout_url' => $data['data']['checkout_url'],
            ];
        }

        throw new \Exception('Unable to redirect to payment', 500);
    }

   public function getUserSales()
    {
        return Sale::where('buyer_id', Auth::id())->with('car')->get();
    }

   public function getSales($id)
{
    $sale = Sale::with('car')->findOrFail($id);

    if ($sale->buyer_id !== Auth::id()) {
        throw new \Exception('Unauthorized', 403);
    }

    return $sale;
}

    // Admin methods
  public function getAllSales()
{
    return Sale::with(['car', 'buyer'])->get(); 
}

  
}