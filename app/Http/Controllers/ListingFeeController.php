<?php

namespace App\Http\Controllers;
use App\Models\ListingFee;
use Illuminate\Http\Request;

class ListingFeeController extends Controller
{
  public function index(Request $request)
    {
        $fees = ListingFee::query()
            ->when($request->item_type, fn($q) => $q->where('item_type', $request->item_type))
            ->when($request->listing_type, fn($q) => $q->where('listing_type', $request->listing_type))
            ->paginate(10);

        return response()->json($fees);
    }
    
  public function store(Request $request)
{
    $data = $request->validate([
        'listing_type' => 'required|in:rent,sell,both',
        'item_type' => 'required|in:car,home',
        'fee' => 'required|numeric',
        'currency' => 'required|string',
        'is_active' => 'nullable',
    ]);



    $fee = ListingFee::create($data);

    return response()->json(['message' => 'Fee created', 'fee' => $fee], 201);
}

   public function show(ListingFee $listingFee)
    {
        return response()->json($listingFee);
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

        $listingFee->update($data);

        return response()->json(['message' => 'Fee updated', 'fee' => $listingFee]);
    }

    public function destroy(ListingFee $listingFee)
    {
        $listingFee->delete();

        return response()->json(['message' => 'Fee deleted']);
    }
}
