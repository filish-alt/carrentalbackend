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
            'price_per_night' => 'required|numeric',
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'max_guests' => 'required|integer',
            'property_type' => 'required|string',
            'status' => 'required|in:available,unavailable,approved,rejected,blocked',
            'amenities' => 'nullable|array',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
                'bedrooms' => $request->bedrooms,
                'bathrooms' => $request->bathrooms,
                'max_guests' => $request->max_guests,
                'property_type' => $request->property_type,
                'status' => $request->status,
                'amenities' => $request->amenities,
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,
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
        $home = Home::findOrFail($id);
        $home->update($request->all());
        return response()->json($home);
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
