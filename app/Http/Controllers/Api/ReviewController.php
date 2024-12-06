<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Review;
use App\Models\Auction;
use Illuminate\Http\Request;
use App\Http\Resources\ReviewResource;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index(User $user)
    {
        $reviews = $user->reviews()
            ->with(['reviewer', 'auction'])
            ->latest()
            ->paginate();

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, User $user)
    {
        // Validate basic fields
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
            'auction_id' => 'nullable|exists:auctions,id'
        ]);

        // If auction_id is provided, verify it belongs to the reviewed user
        if ($request->auction_id) {
            $auction = Auction::findOrFail($request->auction_id);
            if ($auction->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'auction_id' => ['This auction does not belong to the user being reviewed.']
                ]);
            }

            // Verify the reviewer was the winner of the auction
            if ($auction->winner_user_id !== $request->user()->id) {
                throw ValidationException::withMessages([
                    'auction_id' => ['You must be the winner of the auction to review it.']
                ]);
            }
        }

        // Prevent self-reviews
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot review yourself.']
            ]);
        }

        // Check for existing review
        $existingReview = Review::where([
            'reviewer_id' => $request->user()->id,
            'user_id' => $user->id,
            'auction_id' => $request->auction_id
        ])->first();

        if ($existingReview) {
            throw ValidationException::withMessages([
                'review' => ['You have already reviewed this user/auction.']
            ]);
        }

        $review = Review::create([
            'reviewer_id' => $request->user()->id,
            'user_id' => $user->id,
            'auction_id' => $validated['auction_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment']
        ]);

        return new ReviewResource($review->load(['reviewer', 'auction']));
    }

    public function getRating(User $user)
    {
        return response()->json([
            'average_rating' => $user->averageRating(),
            'total_reviews' => $user->reviews()->count(),
            'rating_breakdown' => [
                5 => $user->reviews()->where('rating', 5)->count(),
                4 => $user->reviews()->where('rating', 4)->count(),
                3 => $user->reviews()->where('rating', 3)->count(),
                2 => $user->reviews()->where('rating', 2)->count(),
                1 => $user->reviews()->where('rating', 1)->count(),
            ]
        ]);
    }
}