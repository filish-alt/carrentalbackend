<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HomeReviewService;
use App\Models\Home;

class HomereviewController extends Controller
{
    protected $homeReviewService;

    public function __construct(HomeReviewService $homeReviewService)
    {
        $this->homeReviewService = $homeReviewService;
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'home_id' => 'required|exists:homes,id',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->homeReviewService->createReview($data);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function index()
    {
        $reviews = $this->homeReviewService->getAllReviews();
        return response()->json(['reviews' => $reviews]);
    }

    public function reviewsForHome(Home $home)
    {
        $result = $this->homeReviewService->getReviewsForHome($home->id);
        return response()->json($result);
    }

    public function myhomeReviews()
    {
        $reviews = $this->homeReviewService->getUserReviews();
        return response()->json(['reviews' => $reviews]);
    }

    public function reviewsForMyHomes()
    {
        $reviews = $this->homeReviewService->getReviewsForUserHomes();
        return response()->json(['reviews' => $reviews]);
    }

    public function destroy($id)
    {
        try {
            $result = $this->homeReviewService->deleteReview($id);
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
            $result = $this->homeReviewService->updateReview($data, $id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

}
