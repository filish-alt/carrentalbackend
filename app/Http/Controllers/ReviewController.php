<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;

class ReviewController extends Controller
{

public function store(Request $request)
{
    $request->validate([
        'car_id' => 'required|exists:cars,id',
        'rating' => 'required|integer|min:1|max:5',
        'review_text' => 'required|string|max:1000',
    ]);

    $review = Review::create([
        'user_id' => auth()->id(),
        'car_id' => $request->car_id,
        'rating' => $request->rating,
        'review_text' => $request->review_text,
    ]);

    return response()->json(['message' => 'Review created successfully', 'review' => $review], 201);
}
//get all reviews
public function index()
{
    $reviews = Review::with(['user', 'car'])->get();

    return response()->json(['reviews' => $reviews]);
}

//get review given by the authenticated user
public function myReviews()
{
    $reviews = Review::where('user_id', auth()->id())->with('car')->get();

    return response()->json(['reviews' => $reviews]);
}


// Get all reviews for cars owned by the current user
public function reviewsForMyCars()
{
    $userId = auth()->id();

    $reviews = Review::whereHas('car', function ($query) use ($userId) {
        $query->where('owner_id', $userId);
    })->with(['user', 'car'])->get();

    return response()->json(['reviews' => $reviews]);
}


}
