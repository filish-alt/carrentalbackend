<?php

namespace App\Http\Controllers;

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
        return Car::create($request->all());
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

public function uploadImages(Request $request, $carId)
{
    $request->validate([
        'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    $car = Car::findOrFail($carId);

    if (!$car) {
        return response()->json(['message' => 'Car not found'], 404);
    }
    
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('car_images', 'public');

            CarImage::create([
                'car_id' => $car->id,
                'image_path' => $path,
            ]);
        }
    }

    return response()->json(['message' => 'Images uploaded successfully']);
}
}
