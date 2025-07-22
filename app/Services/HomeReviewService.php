<?php

namespace App\Services;

use App\Models\Homereview;
use App\Models\Home;
use Illuminate\Support\Facades\Auth;

class HomeReviewService
{
    public function createReview(array $data)
    {
        // Check if user already reviewed this home
        $existingReview = Homereview::where('user_id', auth()->id())
            ->where('home_id', $data['home_id'])
            ->first();

        if ($existingReview) {
            throw new \Exception('You have already reviewed this home', 400);
        }

        // Check if user is trying to review their own home
        $home = Home::findOrFail($data['home_id']);
        if ($home->owner_id === auth()->id()) {
            throw new \Exception('You cannot review your own home', 403);
        }

        $review = Homereview::create([
            'user_id' => auth()->id(),
            'home_id' => $data['home_id'],
            'rating' => $data['rating'],
            'review_text' => $data['review_text'],
        ]);

        return [
            'message' => 'Review created successfully',
            'review' => $review->load(['user', 'home'])
        ];
    }

    public function getAllReviews()
    {
        return Homereview::with(['user', 'home'])->get();
    }

    public function getReviewsForHome($homeId)
    {
        $reviews = Homereview::where('home_id', $homeId)
            ->with(['user', 'home'])
            ->get();

        return [
            'reviews' => $reviews,
            'average_rating' => $reviews->avg('rating'),
            'total_reviews' => $reviews->count()
        ];
    }

    public function getUserReviews()
    {
        return Homereview::where('user_id', auth()->id())
            ->with('home')
            ->get();
    }

    public function getReviewsForUserHomes()
    {
        $userId = auth()->id();

        return Homereview::whereHas('home', function ($query) use ($userId) {
            $query->where('owner_id', $userId);
        })->with(['user', 'home'])->get();
    }

    public function deleteReview($id)
    {
        $review = Homereview::find($id);

        if (!$review) {
            throw new \Exception('Review not found', 404);
        }

        // Check if user owns the review or owns the home being reviewed
        $home = Home::find($review->home_id);
        if ($review->user_id !== auth()->id() && $home->owner_id !== auth()->id()) {
            throw new \Exception('Unauthorized to delete this review', 403);
        }

        $review->delete();

        return ['message' => 'Review deleted successfully'];
    }

    public function updateReview(array $data, $id)
    {
        $review = Homereview::find($id);

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
            'review' => $review->fresh(['user', 'home'])
        ];
    }
}
