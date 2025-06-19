<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Homereview;
use App\Models\Home;


class HomereviewController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'home_id' => 'required|exists:homes,id',
        'rating' => 'required|integer|min:1|max:5',
        'review_text' => 'required|string|max:1000',
    ]);

    $review = Homereview::create([
        'user_id' => auth()->id(),
        'home_id' => $request->home_id,
        'rating' => $request->rating,
        'review_text' => $request->review_text,
    ]);

    return response()->json(['message' => 'Review created successfully', 'review' => $review], 201);
}
//get all reviews
public function index()
{
    $reviews = Homereview::with(['user', 'home'])->get();

    return response()->json(['reviews' => $reviews]);
}

//get review for specific home
public function reviewsForHome(Home $home)
{
    $reviews = Homereview::where('home_id', $home->id)
        ->with(['user', 'home'])
        ->get();

    return response()->json(['reviews' => $reviews]);
}

//get review given by the authenticated user
public function myhomeReviews()
{
    $reviews = Homereview::where('user_id', auth()->id())->with('home')->get();

    return response()->json(['reviews' => $reviews]);
}


// Get all reviews for homes owned by the current user
public function reviewsForMyHomes()
{
    $userId = auth()->id();

    $reviews = Homereview::whereHas('home', function ($query) use ($userId) {
        $query->where('owner_id', $userId);
    })->with(['user', 'home'])->get();

    return response()->json(['reviews' => $reviews]);
}

}
