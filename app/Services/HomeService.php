<?php

namespace App\Services;

use App\Models\Home;
use App\Models\HomeImage;
use App\Models\ListingFee;
use App\Models\Platformpayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HomeService
{
    public function getAllHomes()
    {
        return Home::with('images')->get();
    }

    public function createHome(array $data, $request)
    {
        DB::beginTransaction();

        try {
            $home = Home::create([
                'owner_id' => $data['owner_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code'],
                'country' => $data['country'],
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
                'price_per_night' => $data['price_per_night'] ?? null,
                'rent_per_month' => $data['rent_per_month'] ?? null,
                'sell_price' => $data['sell_price'] ?? null,
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'max_guests' => $data['max_guests'],
                'property_type' => $data['property_type'],
                'status' => 'payment_pending',
                'listing_type' => $data['listing_type'],
                'amenities' => $data['amenities'] ?? null,
                'check_in_time' => $data['check_in_time'] ?? null,
                'check_out_time' => $data['check_out_time'] ?? null,
                'furnished' => $data['furnished'] ?? null,
                'area_sqm' => $data['area_sqm'] ?? null,
                'seating_capacity' => $data['seating_capacity'] ?? null,
                'parking' => $data['parking'] ?? null,
                'storage' => $data['storage'] ?? null,
                'loading_zone' => $data['loading_zone'] ?? null,
                'payment_frequency' => $data['payment_frequency'] ?? null,
                'power_supply' => $data['power_supply'] ?? null,
                'kitchen' => $data['kitchen'] ?? null,
                'property_purposes' => $data['property_purposes'] ?? null,
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $this->handleImageUploads($request->file('images'), $home->id);
            }

            // Handle listing fee and payment
            $tx_ref = 'HOMEPOST-' . uniqid();
            $fee = ListingFee::where('item_type', 'home')
                ->where('listing_type', $home->listing_type)
                ->orderByDesc('id')
                ->first();

            if ($fee && $fee->fee > 0) {
                $this->createPlatformPayment($home, $fee, $tx_ref);
                DB::commit();
                return $this->processListingPayment($fee, $tx_ref, $request);
            }

            DB::commit();
            $home->load('images');
            
            return [
                'message' => 'Home created successfully. No payment required.',
                'home' => $home,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function handleImageUploads($images, $homeId)
    {
        foreach ($images as $image) {
            $filename = time() . '_' . $image->getClientOriginalName();
            $destinationPath = base_path('../public_html/home_images');
            $image->move($destinationPath, $filename);

            HomeImage::create([
                'home_id' => $homeId,
                'image_path' => 'home_images/' . $filename,
            ]);
        }
    }

    private function createPlatformPayment($home, $fee, $tx_ref)
    {
        Platformpayment::create([
            'item_id' => $home->id,
            'item_type' => 'home',
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
                'title' => 'Home Listing Fee',
                'description' => 'Payment to publish your home listing.',
            ],
        ];

        Log::info('Chapa Listing Payment Data', $chapaData);

        $response = Http::withToken(env('CHAPA_SECRET_KEY'))
            ->post(env('CHAPA_BASE_URL') . '/transaction/initialize', $chapaData);

        $data = $response->json();

        if ($response->successful() && isset($data['data']['checkout_url'])) {
            return [
                'message' => 'Home created. Redirect to Chapa for payment.',
                'checkout_url' => $data['data']['checkout_url'],
            ];
        }

        throw new \Exception('Unable to redirect to Chapa.', 500);
    }

    public function getHome($id)
    {
        return Home::with('images')->findOrFail($id);
    }

    public function updateHome(array $data, $id)
    {
        $home = Home::findOrFail($id);
        $home->update($data);
        return $home->fresh('images');
    }

    public function deleteHome($id)
    {
        Home::destroy($id);
    }

    public function searchHomes($filters)
    {
        $query = Home::query();

        if (isset($filters['city'])) {
            $query->where('city', 'LIKE', '%' . $filters['city'] . '%');
        }

        if (isset($filters['property_type'])) {
            $query->where('property_type', $filters['property_type']);
        }

        if (isset($filters['bedrooms'])) {
            $query->where('bedrooms', $filters['bedrooms']);
        }

        if (isset($filters['max_guests'])) {
            $query->where('max_guests', '>=', $filters['max_guests']);
        }

        if (isset($filters['listing_type'])) {
            $query->where('listing_type', $filters['listing_type']);
        }

        return $query->with('images')->get();
    }

    public function getHomeImages($homeId)
    {
        $home = Home::with('images')->find($homeId);

        if (!$home) {
            throw new \Exception('Home not found', 404);
        }

        return [
            'message' => 'Home images fetched successfully',
            'images' => $home->images,
        ];
    }

    public function approveHome($id)
    {
        $home = Home::find($id);
        
        if (!$home) {
            throw new \Exception('Home not found', 404);
        }

        $home->status = 'approved';
        $home->save();

        return ['message' => 'Home listing approved'];
    }

    public function rejectHome($id)
    {
        $home = Home::find($id);
        
        if (!$home) {
            throw new \Exception('Home not found', 404);
        }

        $home->status = 'rejected';
        $home->save();

        return ['message' => 'Home listing rejected'];
    }

    public function blockHome($id)
    {
        $home = Home::find($id);
        
        if (!$home) {
            throw new \Exception('Home not found', 404);
        }

        $home->status = 'blocked';
        $home->save();

        return ['message' => 'Home has been blocked'];
    }
}
