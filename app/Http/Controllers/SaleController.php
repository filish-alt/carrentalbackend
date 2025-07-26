<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SaleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
     protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id'
        ]);

        try {
            $result = $this->saleService->createSale($data, $request);
            return response()->json($result);
        }  catch (\Exception $e) {
    Log::error('Sale error', [
        'userId' => auth()->id(),
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString(),
    ]);

    }
  
    return response()->json(['error' => 'Something went wrong'], 500);
}

      
    // List all sales for the authenticated user
    public function index()
    {
        $sales = $this->saleService->getUserSales();
        return response()->json($sales);
    }

    // Show a specific sale
  public function show($id)
{
    try {
        $sale = $this->saleService->getSales($id);
        return response()->json($sale);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
    }
}

     
// ADMIN: List all sales
public function adminIndex()
{
    $sales = $this->saleService->getAllSales();
    return response()->json([
        'message' => 'All sales fetched successfully.',
        'bookings' => $sales
    ]);
}



}
