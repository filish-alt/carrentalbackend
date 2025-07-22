<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReviewService;
use App\Models\Car;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

public function store(Request $request)
{
    $data = $request->validate([
        'car_id' => 'required|exists:cars,id',
        'rating' => 'required|integer|min:1|max:5',
        'review_text' => 'required|string|max:1000',
    ]);

    try {
        $result = $this->reviewService->createReview($data);
        return response()->json($result, 201);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
    }
}

public function index()
{
    $reviews = $this->reviewService->getAllReviews();
    return response()->json(['reviews' => $reviews]);
}

public function reviewsForCar(Car $car)
{
    $result = $this->reviewService->getReviewsForCar($car->id);
    return response()->json($result);
}

public function myReviews()
{
    $reviews = $this->reviewService->getUserReviews();
    return response()->json(['reviews' => $reviews]);
}

public function reviewsForMyCars()
{
    $reviews = $this->reviewService->getReviewsForUserCars();
    return response()->json(['reviews' => $reviews]);
}

public function destroy($id)
{
    try {
        $result = $this->reviewService->deleteReview($id);
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
    }
}

public function update(Request $request, $id)
{
    $data = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'review_text' => 'required|string|max:1000',
    ]);

    try {
        $result = $this->reviewService->updateReview($data, $id);
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
    }
}

}
