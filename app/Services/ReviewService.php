<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Car;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    public function createReview(array $data)
    {
        // Check if user already reviewed this car
        $existingReview = Review::where('user_id', auth()->id())
            ->where('car_id', $data['car_id'])
            ->first();

        if ($existingReview) {
            throw new \Exception('You have already reviewed this car', 400);
        }

        // Check if user is trying to review their own car
        $car = Car::findOrFail($data['car_id']);
        if ($car->owner_id === auth()->id()) {
            throw new \Exception('You cannot review your own car', 403);
        }

        $review = Review::create([
            'user_id' => auth()->id(),
            'car_id' => $data['car_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'],
        ]);

        return [
            'message' => 'Review created successfully',
            'review' => $review->load(['user', 'car'])
        ];
    }

    public function getAllReviews()
    {
        return Review::with(['user', 'car'])->get();
    }

    public function getReviewsForCar($carId)
    {
        $reviews = Review::where('car_id', $carId)
            ->with(['user', 'car'])
            ->get();

        return [
            'reviews' => $reviews,
            'average_rating' => $reviews->avg('rating'),
            'total_reviews' => $reviews->count()
        ];
    }

    public function getUserReviews()
    {
        return Review::where('user_id', auth()->id())
            ->with('car')
            ->get();
    }

    public function getReviewsForUserCars()
    {
        $userId = auth()->id();

        return Review::whereHas('car', function ($query) use ($userId) {
            $query->where('owner_id', $userId);
        })->with(['user', 'car'])->get();
    }

    public function deleteReview($id)
    {
        $review = Review::find($id);

        if (!$review) {
            throw new \Exception('Review not found', 404);
        }

        // Check if user owns the review or owns the car being reviewed
        $car = Car::find($review->car_id);
        if ($review->user_id !== auth()->id() && $car->owner_id !== auth()->id()) {
            throw new \Exception('Unauthorized to delete this review', 403);
        }

        $review->delete();

        return ['message' => 'Review deleted successfully'];
    }

    public function updateReview(array $data, $id)
    {
        $review = Review::find($id);

        if (!$review) {
            throw new \Exception('Review not found', 404);
        }

        if ($review->user_id !== auth()->id()) {
            throw new \Exception('Unauthorized to update this review', 403);
        }

        $review->update([
            'rating' => $data['rating'],
            'review_text' => $data['review_text'],
        ]);

        return [
            'message' => 'Review updated successfully',
            'review' => $review->fresh(['user', 'car'])
        ];
    }
}
