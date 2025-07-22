<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ListingFee;
use App\Services\ListingFeeService;
use Illuminate\Http\Request;

class ListingFeeController extends Controller
{
    protected $listingFeeService;

    public function __construct(ListingFeeService $listingFeeService)
    {
        $this->listingFeeService = $listingFeeService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['item_type', 'listing_type']);
        $fees = $this->listingFeeService->getAllFees($filters);

        return response()->json($fees);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'listing_type' => 'required|in:rent,sell,both',
            'item_type' => 'required|in:car,home',
            'fee' => 'required|numeric',
            'currency' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $result = $this->listingFeeService->createFee($data);

        return response()->json($result, 201);
    }

    public function show($id)
    {
        $fee = $this->listingFeeService->getFee($id);

        return response()->json($fee);
    }

    public function update(Request $request, ListingFee $listingFee)
    {
        $data = $request->validate([
            'listing_type' => 'required|in:rent,sell,both',
            'item_type' => 'required|in:car,home',
            'fee' => 'required|numeric',
            'currency' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $result = $this->listingFeeService->updateFee($data, $listingFee);

        return response()->json($result);
    }

    public function destroy(ListingFee $listingFee)
    {
        $result = $this->listingFeeService->deleteFee($listingFee);

        return response()->json($result);
    }
}
