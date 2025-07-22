<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CarService;
use Illuminate\Support\Facades\Auth;



class CarController extends Controller
{
    protected $carService;

    public function __construct(CarService $carService)
    {
        $this->carService = $carService;
    }

    /**
     * @OA\Get(
     *     path="/api/cars",
     *     summary="Get all cars",
     *     tags={"Car Management"},
     *     description="Retrieve a list of all available cars with their images",
     *     @OA\Response(
     *         response=200,
     *         description="List of cars retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Car")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return $this->carService->getAllCars();
    }

    /**
     * @OA\Get(
     *     path="/api/mycars",
     *     summary="Get user's cars",
     *     tags={"Car Management"},
     *     description="Retrieve all cars owned by the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User's cars retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Car")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function myCars()
    {
        return response()->json($this->carService->getUserCars());
    }

    /**
     * @OA\Post(
     *     path="/api/cars",
     *     summary="Create a new car listing",
     *     tags={"Car Management"},
     *     description="Create a new car listing with images and payment processing for listing fees",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"owner_id", "make", "model", "seating_capacity", "license_plate", "status", "price_per_day", "fuel_type", "transmission", "listing_type"},
     *                 @OA\Property(property="owner_id", type="integer", example=1, description="ID of the car owner"),
     *                 @OA\Property(property="make", type="string", example="Toyota", description="Car manufacturer"),
     *                 @OA\Property(property="model", type="string", example="Camry", description="Car model"),
     *                 @OA\Property(property="vin", type="string", example="1HGBH41JXMN109186", description="Vehicle identification number (optional)"),
     *                 @OA\Property(property="seating_capacity", type="integer", example=5, description="Number of seats"),
     *                 @OA\Property(property="license_plate", type="string", example="AB123CD", description="Car license plate"),
     *                 @OA\Property(property="status", type="string", example="Available", description="Car status"),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=50.00, description="Daily rental price"),
     *                 @OA\Property(property="fuel_type", type="string", enum={"Gasoline", "Diesel", "Electric", "Hybrid"}, example="Gasoline"),
     *                 @OA\Property(property="transmission", type="string", enum={"Manual", "Automatic"}, example="Automatic"),
     *                 @OA\Property(property="location_lat", type="number", format="float", example=9.0320, description="Latitude (optional)"),
     *                 @OA\Property(property="location_long", type="number", format="float", example=38.7615, description="Longitude (optional)"),
     *                 @OA\Property(property="pickup_location", type="string", example="Addis Ababa Airport", description="Pickup location (optional)"),
     *                 @OA\Property(property="return_location", type="string", example="Addis Ababa Airport", description="Return location (optional)"),
     *                 @OA\Property(property="listing_type", type="string", enum={"rent", "sell", "both"}, example="rent", description="Type of listing"),
     *                 @OA\Property(property="sell_price", type="number", format="float", example=25000.00, description="Sale price (if selling)"),
     *                 @OA\Property(property="is_negotiable", type="boolean", example=true, description="Whether price is negotiable"),
     *                 @OA\Property(property="mileage", type="integer", example=50000, description="Car mileage (optional)"),
     *                 @OA\Property(property="year", type="integer", example=2020, description="Manufacturing year (optional)"),
     *                 @OA\Property(property="condition", type="string", enum={"New", "Excellent", "Good", "Fair", "Poor"}, example="Good", description="Car condition (optional)"),
     *                 @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary"), description="Car images (max 2MB each)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Car created successfully with payment redirect",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Car created. Redirect to Chapa for payment."),
     *             @OA\Property(property="checkout_url", type="string", example="https://checkout.chapa.co/checkout/payment/...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Car created successfully without payment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Car created successfully. No payment required."),
     *             @OA\Property(property="car", ref="#/components/schemas/Car")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error: Database transaction failed")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|integer',
            'make' => 'required|string',
            'model' => 'required|string',
            'vin' => 'nullable|string|unique:cars,vin',
            'seating_capacity' => 'required|integer',
            'license_plate' => 'nullable|string|unique:cars,license_plate',
            'price_per_day' => 'required|numeric',
            'fuel_type' => 'required|string',
            'transmission' => 'required|string',
            'location_lat' => 'nullable|numeric',
            'location_long' => 'nullable|numeric',
            'pickup_location' => 'nullable|string',
            'return_location' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'listing_type' => 'required|in:rent,sell,both',
            'sell_price' => 'nullable|numeric',
            'is_negotiable' => 'nullable',
            'mileage' => 'nullable|integer',
            'year' => 'nullable|integer',
            'condition' => 'nullable|string',
        ]);

        try {
            $result = $this->carService->createCar($data, $request);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            return $this->carService->getCar($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'make' => 'sometimes|string',
            'model' => 'sometimes|string',
            'vin' => 'nullable|string',
            'seating_capacity' => 'sometimes|integer',
            'license_plate' => 'sometimes|string',
            'status' => 'sometimes|string',
            'price_per_day' => 'sometimes|numeric',
            'fuel_type' => 'sometimes|string',
            'transmission' => 'sometimes|string',
            'location_lat' => 'nullable|numeric',
            'location_long' => 'nullable|numeric',
            'pickup_location' => 'nullable|string',
            'return_location' => 'nullable|string',
            'listing_type' => 'sometimes|in:rent,sell,both',
            'sell_price' => 'nullable|numeric',
            'is_negotiable' => 'nullable|boolean',
            'mileage' => 'nullable|integer',
            'year' => 'nullable|integer',
            'condition' => 'nullable|string',
        ]);

        try {
            $car = $this->carService->updateCar($data, $id);
            return response()->json($car);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $this->carService->deleteCar($id);
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function search(Request $request)
    {
        $filters = $request->only(['make', 'model', 'seating_capacity', 'transmission', 'listing_type']);
        $cars = $this->carService->searchCars($filters);
        return response()->json($cars);
    }

    public function getCarImages($carId)
    {
        try {
            $result = $this->carService->getCarImages($carId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function approveCar($id)
    {
        try {
            $result = $this->carService->approveCar($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function CarStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        try {
            $result = $this->carService->updateCarStatus($id, $request->status);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function rejectCar($id)
    {
        try {
            $result = $this->carService->rejectCar($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function blockCar($id)
    {
        try {
            $result = $this->carService->blockCar($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }
}
