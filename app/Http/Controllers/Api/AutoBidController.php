<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AutoBid;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\AutoBidResource;

class AutoBidController extends Controller
{
    public function index(Request $request)
    {
        $autoBids = $request->user()
            ->autoBids()
            ->with(['auction'])
            ->latest()
            ->paginate();

        return AutoBidResource::collection($autoBids);
    }

    public function setup(Request $request, Auction $auction)
    {
        // Validate request
        $validated = $request->validate([
            'max_amount' => 'required|numeric|min:' . ($auction->start_price + 0.01),
            'bid_increment' => 'required|numeric|min:0.01',
        ]);

        // Check if auction is active
        if ($auction->status !== 'active') {
            throw ValidationException::withMessages([
                'auction' => ['This auction is not active.']
            ]);
        }

        // Check if user already has an auto-bid for this auction
        $existingAutoBid = $request->user()->autoBids()
            ->where('auction_id', $auction->id)
            ->first();

        if ($existingAutoBid) {
            // Update existing auto-bid
            $existingAutoBid->update([
                'max_amount' => $validated['max_amount'],
                'bid_increment' => $validated['bid_increment'],
                'active' => true
            ]);

            $autoBid = $existingAutoBid;
        } else {
            // Create new auto-bid
            $autoBid = $request->user()->autoBids()->create([
                'auction_id' => $auction->id,
                'max_amount' => $validated['max_amount'],
                'bid_increment' => $validated['bid_increment']
            ]);
        }

        // Process initial auto-bid if needed
        $this->processAutoBid($autoBid);

        return new AutoBidResource($autoBid->load('auction'));
    }

    public function destroy(AutoBid $autoBid)
    {
        $this->authorize('delete', $autoBid);
        
        $autoBid->update(['active' => false]);
        
        return response()->json(['message' => 'Auto-bid deactivated successfully']);
    }

    private function processAutoBid(AutoBid $autoBid)
    {
        $auction = $autoBid->auction;
        $currentHighestBid = $auction->bids()->orderBy('amount', 'desc')->first();
        $currentAmount = $currentHighestBid ? $currentHighestBid->amount : $auction->start_price;

        // If current highest bid is from the same user, don't process
        if ($currentHighestBid && $currentHighestBid->user_id === $autoBid->user_id) {
            return;
        }

        // Calculate next bid amount
        $nextBidAmount = $currentAmount + $autoBid->bid_increment;

        // Check if next bid amount is within max amount
        if ($nextBidAmount <= $autoBid->max_amount) {
            // Place the bid
            $bid = $auction->bids()->create([
                'user_id' => $autoBid->user_id,
                'amount' => $nextBidAmount
            ]);

            // Process other auto-bids
            $this->processOtherAutoBids($auction, $autoBid->user_id);
        }
    }

    private function processOtherAutoBids(Auction $auction, $excludeUserId)
    {
        $otherAutoBids = $auction->autoBids()
            ->where('user_id', '!=', $excludeUserId)
            ->where('active', true)
            ->orderBy('max_amount', 'desc')
            ->get();

        foreach ($otherAutoBids as $autoBid) {
            $this->processAutoBid($autoBid);
        }
    }
} 