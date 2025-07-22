<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HomeService;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    protected $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }
    
    public function index()
    {
        return $this->homeService->getAllHomes();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|integer|exists:users,id',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'price_per_night' => 'nullable|numeric',
            'rent_per_month' => 'nullable|numeric',
            'sell_price' => 'nullable|numeric',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'max_guests' => 'required|integer',
            'property_type' => 'required|string',
            'listing_type' => 'required|in:rent,sell,both',
            'amenities' => 'nullable|array',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'furnished' => 'nullable|in:furnished,semi-furnished,unfurnished',
            'area_sqm' => 'nullable|numeric',
            'seating_capacity' => 'nullable|integer',
            'parking' => 'nullable|string',
            'storage' => 'nullable|in:Available,Not Available',
            'loading_zone' => 'nullable|in:Available,Not Available',
            'payment_frequency' => 'nullable|in:one time,Daily,Weekly,Monthly,Yearly',
            'power_supply' => 'nullable|in:Single Phase,Three Phase',
            'kitchen' => 'nullable|in:Traditional,Modern,Both,none',
            'property_purposes' => 'nullable|array',
            'property_purposes.*' => 'in:residential,office,business,store,celebration',
        ]);

        try {
            $result = $this->homeService->createHome($data, $request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return Home::with('images')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $request->validate([
            'title' => 'sometimes|string',
            'description' => 'nullable|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'zip_code' => 'sometimes|string',
            'country' => 'sometimes|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'price_per_night' => 'nullable|numeric',
            'rent_per_month' => 'nullable|numeric',
            'sell_price' => 'nullable|numeric',
            'bedrooms' => 'sometimes|integer',
            'bathrooms' => 'sometimes|integer',
            'max_guests' => 'sometimes|integer',
            'property_type' => 'sometimes|string',
            'status' => 'sometimes|in:available,unavailable,approved,rejected,blocked',
            'listing_type' => 'sometimes|in:rent,sell,both',
            'amenities' => 'nullable|array',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',

            // New fields
            'furnished' => 'sometimes|in:furnished,semi-furnished,unfurnished',
            'area_sqm' => 'nullable|numeric',
            'seating_capacity' => 'nullable|integer',
            'parking' => 'nullable|string',
            'storage' => 'nullable|in:Available,Not Available',
            'loading_zone' => 'nullable|in:Available,Not Available',
            'payment_frequency' => 'nullable|in:one time,Daily,Weekly,Monthly,Yearly',
            'power_supply' => 'nullable|in:Single Phase,Three Phase',
            'kitchen' => 'nullable|in:Traditional,Modern,Both,none',
            'property_purposes' => 'nullable|array',
            'property_purposes.*' => 'in:residential,office,business,store,celebration',
        ]);

        $home = Home::findOrFail($id);
        $home->update($request->all());

        return response()->json($home->fresh('images'));
    }

    public function destroy($id)
    {
        $user = Auth::user();
        Home::destroy($id);
        return response()->noContent();
    }

    public function search(Request $request)
    {
        $query = Home::query();

        if ($request->has('city')) {
            $query->where('city', 'LIKE', '%' . $request->city . '%');
        }

        if ($request->has('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->has('bedrooms')) {
            $query->where('bedrooms', $request->bedrooms);
        }

        if ($request->has('max_guests')) {
            $query->where('max_guests', '>=', $request->max_guests);
        }

        if ($request->has('listing_type')) {
            $query->where('listing_type', $request->listing_type);
        }

        return response()->json($query->with('images')->get());
    }

    public function getHomeImages($homeId)
    {
        $home = Home::with('images')->find($homeId);

        if (!$home) {
            return response()->json(['message' => 'Home not found'], 404);
        }

        return response()->json([
            'message' => 'Home images fetched successfully',
            'images' => $home->images,
        ]);
    }

    public function approveHome($id)
    {
        $home = Home::find($id);
        if (!$home) {
            return response()->json(['message' => 'Home not found'], 404);
        }

        $home->status = 'approved';
        $home->save();

        return response()->json(['message' => 'Home listing approved']);
    }

    public function rejectHome($id)
    {
        $home = Home::find($id);
        if (!$home) {
            return response()->json(['message' => 'Home not found'], 404);
        }

        $home->status = 'rejected';
        $home->save();

        return response()->json(['message' => 'Home listing rejected']);
    }

    public function blockHome($id)
    {
        $home = Home::find($id);
        if (!$home) {
            return response()->json(['message' => 'Home not found'], 404);
        }

        $home->status = 'blocked';
        $home->save();

        return response()->json(['message' => 'Home has been blocked']);
    }
}
