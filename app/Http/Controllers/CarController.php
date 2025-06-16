<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\ListingFee;
use App\Models\Platformpayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;



class CarController extends Controller
{
    public function index()
    {
        return Car::with('images')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|integer',
            'make'  =>  'required|string',
            'model' => 'required|string',
            'vin'   => 'nullable|string|unique:cars,vin',
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

            // Sale fields
            'listing_type' => 'required|in:rent,sell,both',
            'sell_price' => 'nullable|numeric',
            'is_negotiable' => 'nullable',
            'mileage' => 'nullable|integer',
            'year' => 'nullable|integer',
            'condition' => 'nullable|string',
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
                'status' =>'pending_payment',
                'price_per_day' => $request->price_per_day,
                'fuel_type' => $request->fuel_type,
                'transmission' => $request->transmission,
                'location_lat' => $request->location_lat ?? 8.9831,
                'location_long' => $request->location_long ?? 38.8101,
                'pickup_location' => $request->pickup_location,
                'return_location' => $request->return_location,

                // Sale fields
                'listing_type' => $request->listing_type,
                'sell_price' => $request->sell_price,
                'is_negotiable' => $request->is_negotiable ?? false,
                'mileage' => $request->mileage,
                'year' => $request->year,
                'condition' => $request->condition,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $filename = time() . '_' . $image->getClientOriginalName();
                    $destinationPath = base_path('../public_html/car_images');
                    $image->move($destinationPath, $filename);

                    CarImage::create([
                        'car_id' => $car->id,
                        'image_path' => 'car_images/' . $filename,
                    ]);
                }
            }
        // Generate tx_ref and save payment
        $tx_ref = 'CARPOST-' . uniqid();
        $fee = ListingFee::where('item_type', 'car')
            ->whereIn('listing_type', ['rent', 'both'])
            ->orderByDesc('id') // get the latest active fee
            ->first();
        if($fee) {
          Platformpayment::create([
            'item_id'=>$car->id,
            'amount' => $fee->fee, // Set your fixed posting fee here
            'currency' => $fee->currency,
            'payment_status' => 'pending',
            'payment_method' => 'chapa',
            'transaction_date' => now(),
            'tx_ref' => $tx_ref,
        ]);
    }
    
        DB::commit();
        
        $returnUrl = $request->header('Platform') === 'mobile'
            ? env('Payment_MOBILE_RETURN_URL') . '?tx_ref=' . $tx_ref
            : env('Payment_FRONTEND_RETURN_URL') . '?tx_ref=' . $tx_ref;
        
        $chapaData = [
            'amount' => 200,
            'currency' => 'ETB',
            'email' => auth()->user()->email,
            'first_name' => auth()->user()->first_name,
            'phone_number' => auth()->user()->phone,
            'tx_ref' => $tx_ref,
            'callback_url' => url('/api/chapa/listing-callback'),
            'return_url' => $returnUrl,
            'customization' => [
                'title' => 'Car Listing Fee',
                'description' => 'Payment to publish your car listing.',
            ],
        ];
     
        Log::info('Chapa Listing Payment Data', $chapaData);

        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);

        $data = $response->json();

        if ($response->successful() && isset($data['data']['checkout_url'])) {
            return response()->json([
                'message' => 'Car created. Redirect to Chapa for payment.',
                'checkout_url' => $data['data']['checkout_url'],
            ]);
        }

        return response()->json(['error' => 'Unable to redirect to Chapa.'], 500);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return Car::with('images')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);

        $request->validate([
            'make' => 'sometimes|string',
            'model' => 'sometimes|string',
            'vin' => 'nullable|string|unique:cars,vin,' . $car->id,
            'seating_capacity' => 'sometimes|integer',
            'license_plate' => 'sometimes|string|unique:cars,license_plate,' . $car->id,
            'status' => 'sometimes|string',
            'price_per_day' => 'sometimes|numeric',
            'fuel_type' => 'sometimes|string',
            'transmission' => 'sometimes|string',
            'location_lat' => 'nullable|numeric',
            'location_long' => 'nullable|numeric',
            'pickup_location' => 'nullable|string',
            'return_location' => 'nullable|string',

            // Sale fields
            'listing_type' => 'sometimes|in:rent,sell,both',
            'sell_price' => 'nullable|numeric',
            'is_negotiable' => 'nullable|boolean',
            'mileage' => 'nullable|integer',
            'year' => 'nullable|integer',
            'condition' => 'nullable|string',
        ]);

        $car->update($request->all());

        return response()->json($car->fresh('images'));
    }

    public function destroy($id)
    {
        Car::destroy($id);
        return response()->noContent();
    }

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

        if ($request->has('listing_type')) {
            $query->where('listing_type', $request->listing_type);
        }

        return response()->json($query->with('images')->get());
    }

    public function getCarImages($carId)
    {
        $car = Car::with('images')->find($carId);

        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        return response()->json([
            'message' => 'Car images fetched successfully',
            'images' => $car->images,
        ]);
    }

    public function approveCar($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->status = 'approved';
        $car->save();

        return response()->json(['message' => 'Car listing approved']);
    }

    public function rejectCar($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->status = 'rejected';
        $car->save();

        return response()->json(['message' => 'Car listing rejected']);
    }

    public function blockCar($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['message' => 'Car not found'], 404);
        }

        $car->status = 'blocked';
        $car->save();

        return response()->json(['message' => 'Car has been blocked']);
    }
}
