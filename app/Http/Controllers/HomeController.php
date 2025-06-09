<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Models\HomeImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function index()
    {
        return Home::with('images')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
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
            'status' => 'required|in:available,unavailable,approved,rejected,blocked',
            'listing_type' => 'required|in:rent,sell,both',
            'amenities' => 'nullable|array',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            // New fields
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

        DB::beginTransaction();

        try {
            $home = Home::create([
                'owner_id' => $request->owner_id,
                'title' => $request->title,
                'description' => $request->description,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'country' => $request->country,
                'latitude' => $request->latitude ?? 0,
                'longitude' => $request->longitude ?? 0,
                'price_per_night' => $request->price_per_night,
                'rent_per_month' => $request->rent_per_month,
                'sell_price' => $request->sell_price,
                'bedrooms' => $request->bedrooms,
                'bathrooms' => $request->bathrooms,
                'max_guests' => $request->max_guests,
                'property_type' => $request->property_type,
                'status' => $request->status,
                'listing_type' => $request->listing_type,
                'amenities' => $request->amenities,
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,

                // New fields
                'furnished' => $request->furnished,
                'area_sqm' => $request->area_sqm,
                'seating_capacity' => $request->seating_capacity,
                'parking' => $request->parking,
                'storage' => $request->storage,
                'loading_zone' => $request->loading_zone,
                'payment_frequency' => $request->payment_frequency,
                'power_supply' => $request->power_supply,
                'kitchen' => $request->kitchen,
                'property_purposes' => $request->property_purposes,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $destinationPath = base_path('../public_html/home_images');
                    $image->move($destinationPath, $filename);

                    HomeImage::create([
                        'home_id' => $home->id,
                        'image_path' => 'home_images/' . $filename,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Home and images created successfully',
                'home' => $home->load('images'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return Home::with('images')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
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
