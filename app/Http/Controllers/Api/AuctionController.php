<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Http\Resources\AuctionResource;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    // ... existing methods ...

    /**
     * Get comments for an auction
     */
    public function comments(Auction $auction)
    {
        $comments = $auction->comments()
            ->whereNull('parent_id')  // Get only top-level comments
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate();

        return CommentResource::collection($comments);
    }

    /**
     * Add a comment to an auction
     */
    public function addComment(Request $request, Auction $auction)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id'
        ]);

        // If parent_id is provided, verify it belongs to the same auction
        if ($request->parent_id) {
            $parentComment = Comment::findOrFail($request->parent_id);
            if ($parentComment->auction_id !== $auction->id) {
                return response()->json([
                    'message' => 'Parent comment does not belong to this auction'
                ], 422);
            }
        }

        $comment = $auction->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null
        ]);

        // Load relationships for the response
        $comment->load(['user', 'replies.user']);

        return new CommentResource($comment);
    }

    /**
     * Show a single auction with its comments
     */
    public function show(Auction $auction)
    {
        $auction->load([
            'category', 
            'images', 
            'user',
            'comments' => function ($query) {
                $query->whereNull('parent_id')
                      ->with(['user', 'replies.user'])
                      ->latest();
            }
        ]);

        return new AuctionResource($auction);
    }
} 