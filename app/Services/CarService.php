<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\ListingFee;
use App\Models\Platformpayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;


class CarService
{
    public function getAllCars()
    {
        return Car::with('images')->get();
    }

    public function getUserCars()
    {
        $userId = auth()->id();
        return Car::with('images')
            ->where('owner_id', $userId)
            ->get();
    }

    public function createCar(array $data, $request)
    {
        DB::beginTransaction();
        
        try {
            // Create the car
            $car = Car::create([
                'owner_id' => $data['owner_id'],
                'make' => $data['make'],
                'model' => $data['model'],
                'vin' => $data['vin'] ?? null,
                'seating_capacity' => $data['seating_capacity'],
                'license_plate' => $data['license_plate'] ?? null,
                'status' => 'pending',
                'price_per_day' => $data['price_per_day'],
                'fuel_type' => $data['fuel_type'],
                'transmission' => $data['transmission'],
                'location_lat' => $data['location_lat'] ?? 8.9831,
                'location_long' => $data['location_long'] ?? 38.8101,
                'pickup_location' => $data['pickup_location'] ?? null,
                'return_location' => $data['return_location'] ?? null,
                'listing_type' => $data['listing_type'],
                'sell_price' => $data['sell_price'] ?? null,
                'is_negotiable' => $data['is_negotiable'] ?? false,
                'mileage' => $data['mileage'] ?? null,
                'year' => $data['year'] ?? null,
                'condition' => $data['condition'] ?? null,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $this->handleImageUploads($request->file('images'), $car->id);
            }

            // Handle listing fee and payment
            $fee = ListingFee::where('item_type', 'car')
                ->where('listing_type', $car->listing_type)
                ->orderByDesc('id')
                ->first();

            if ($fee && $fee->fee > 0) {
                $tx_ref = 'CARPOST-' . uniqid();
                
                $this->createPlatformPayment($car, $fee, $tx_ref);
                
                DB::commit();
                
                return $this->processListingPayment($fee, $tx_ref, $request);
            }

            DB::commit();
            $car->load('images');
            
            return [
                'message' => 'Car created successfully. No payment required.',
                'car' => $car,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

private function handleImageUploads($images, $carId)
{
  $manager = new ImageManager(new Driver());

    foreach ($images as $image) {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $webpFileName = time() . '_' . preg_replace('/\s+/', '_', $originalName) . '.webp';
        $destinationPath = base_path('../public_html/car_images');

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Convert to WebP and save
        $webpImage = $manager->read($image)->toWebp(80);
        $webpImage->save("{$destinationPath}/{$webpFileName}");

        // Save image path to DB
        CarImage::create([
            'car_id' => $carId,
            'image_path' => 'car_images/' . $webpFileName,
        ]);
    }
}

    private function createPlatformPayment($car, $fee, $tx_ref)
    {
        Platformpayment::create([
            'item_id' => $car->id,
            'item_type' => 'car',
            'amount' => $fee->fee,
            'currency' => $fee->currency,
            'payment_status' => 'pending',
            'payment_method' => 'chapa',
            'transaction_date' => now(),
            'tx_ref' => $tx_ref,
        ]);
    }

    private function processListingPayment($fee, $tx_ref, $request)
    {
        $returnUrl = $request->header('Platform') === 'mobile'
            ? url('/api/redirect/payment') . '?tx_ref=' . $tx_ref
            : env('Payment_FRONTEND_RETURN_URL') . '?tx_ref=' . $tx_ref;

        Log::info('Return URL', ['url' => $returnUrl]);

        $chapaData = [
            'amount' => $fee->fee,
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
            return [
                'message' => 'Car created. Redirect to Chapa for payment.',
                'checkout_url' => $data['data']['checkout_url'],
            ];
        }

        throw new \Exception('Unable to redirect to Chapa', 500);
    }

    public function getCar($id)
    {
        return Car::with('images')->findOrFail($id);
    }

    public function updateCar(array $data, $id)
    {
        $car = Car::findOrFail($id);
        $car->update($data);
        return $car->fresh('images');
    }

    public function deleteCar($id)
    {
        Car::destroy($id);
    }

    public function searchCars($filters)
    {
        $query = Car::query();

        if (isset($filters['make'])) {
            $query->where('make', 'LIKE', '%' . $filters['make'] . '%');
        }

        if (isset($filters['model'])) {
            $query->where('model', 'LIKE', '%' . $filters['model'] . '%');
        }

        if (isset($filters['seating_capacity'])) {
            $query->where('seating_capacity', $filters['seating_capacity']);
        }

        if (isset($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (isset($filters['listing_type'])) {
            $query->where('listing_type', $filters['listing_type']);
        }

        return $query->with('images')->get();
    }

    public function getCarImages($carId)
    {
        $car = Car::with('images')->find($carId);

        if (!$car) {
            throw new \Exception('Car not found', 404);
        }

        return [
            'message' => 'Car images fetched successfully',
            'images' => $car->images,
        ];
    }

    public function approveCar($id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            throw new \Exception('Car not found', 404);
        }

        $car->status = 'available';
        $car->save();

        return ['message' => 'Car listing approved'];
    }

    public function updateCarStatus($id, $status)
    {
        $car = Car::find($id);
        
        if (!$car) {
            throw new \Exception('Car not found', 404);
        }

        $car->status = $status;
        $car->save();

        return [
            'message' => 'Car listing status updated successfully',
            'car' => $car
        ];
    }

    public function rejectCar($id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            throw new \Exception('Car not found', 404);
        }

        $car->status = 'rejected';
        $car->save();

        return ['message' => 'Car listing rejected'];
    }

    public function blockCar($id)
    {
        $car = Car::find($id);
        
        if (!$car) {
            throw new \Exception('Car not found', 404);
        }

        $car->status = 'blocked';
        $car->save();

        return ['message' => 'Car has been blocked'];
    }
}
