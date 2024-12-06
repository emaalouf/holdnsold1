<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    public function index(Auction $auction)
    {
        $comments = $auction->comments()
            ->whereNull('parent_id')  // Get only top-level comments
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate();

        return CommentResource::collection($comments);
    }

    public function store(Request $request, Auction $auction)
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

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        
        $comment->delete();
        
        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }
} 