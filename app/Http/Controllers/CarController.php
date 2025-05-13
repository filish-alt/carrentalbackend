<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    public function index()
    {
        return Car::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|integer',
            'make'  =>  'required|string',
            'model' => 'required|string',
            'vin'   =>   'nullable|string|unique:cars,vin',
            'seating_capacity' => 'required|integer',
            'license_plate' => 'required|string|unique:cars,license_plate',
            'status' => 'required|string',
            'price_per_day' => 'required|numeric',
            'fuel_type' => 'required|string',
            'transmission' => 'required|string',
            'location_lat' => 'nullable|numeric',
            'location_long' => 'nullable|numeric',
            'pickup_location' => 'nullable|string',
            'return_location' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $car = Car::create([
                'owner_id' => $request->owner_id,
                'make' => $request->make,
                'model' => $request->model,
                'vin' => $request->vin,
                'seating_capacity' => $request->seating_capacity,
                'license_plate' => $request->license_plate,
                'status' => $request->status,
                'price_per_day' => $request->price_per_day,
                'fuel_type' => $request->fuel_type,
                'transmission' => $request->transmission,
                'location_lat' => $request->location_lat ?? 8.9831,
                'location_long' => $request->location_long ?? 38.8101,
                'pickup_location' => $request->pickup_location,
                'return_location' => $request->return_location,
            ]);

        //  if ($request->hasFile('images')) {
        //         foreach ($request->file('images') as $image) {
        //             $path = $image->store('car_images', 'public');
    
        //             CarImage::create([
        //                 'car_id' => $car->id,
        //                 'image_path' => $path,
        //             ]);
        //         }
        //     }
          if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();

        // Save to public_html/car_images/
        $destinationPath = base_path('../public_html/car_images'); // outside Laravel project folder, directly in public_html
        $image->move($destinationPath, $filename);
        
                    CarImage::create([
                        'car_id' => $car->id,
                         'image_path' => 'car_images/' . $filename,
                    ]);
                }
            }
      DB::commit();

        return response()->json([
            'message' => 'Car and images created successfully',
            'car' => $car->load('images'),
        ], 201);
    
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
    }

    }

    public function show($id)
    {
        return Car::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);
        $car->update($request->all());
        return $car;
    }

    public function destroy($id)
    {
        Car::destroy($id);
        return response()->noContent();
    }

    //search car by different attributes


  public function search(Request $request)
    {
        $query = Car::query();

        if ($request->has('make')) {
            $query->where('make', 'LIKE', '%' . $request->make . '%');
        }

        if ($request->has('model')) {
            $query->where('model', 'LIKE', '%' . $request->model . '%');
        }

        if ($request->has('seating_capacity')) {
            $query->where('seating_capacity', $request->seating_capacity);
        }

        if ($request->has('transmission')) {
            $query->where('transmission', $request->transmission);
        }

        $cars = $query->get();

        return response()->json($cars);
    }
public function getCarImages($carId)
    {
        // Fetch the car with its related images
        $car = Car::with('images')->find($carId);
    
        // Check if the car exists
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }
    
        return response()->json([
            'message' => 'Car images fetched successfully',
            'images' => $car->images,
        ]);
    }
    

}
