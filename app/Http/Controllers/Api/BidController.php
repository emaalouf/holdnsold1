<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Notifications\NewBidNotification;
use Illuminate\Support\Facades\DB;

class BidController extends Controller
{
    public function store(Request $request, Auction $auction)
    {
        // Validate bid amount
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        // Check if auction is active
        if ($auction->status !== 'active') {
            throw ValidationException::withMessages([
                'auction' => ['This auction is not active.'],
            ]);
        }

        // Check if auction has ended
        if (now() > $auction->end_time) {
            throw ValidationException::withMessages([
                'auction' => ['This auction has ended.'],
            ]);
        }

        // Get highest bid
        $highestBid = $auction->bids()->orderBy('amount', 'desc')->first();
        $minimumBid = $highestBid ? $highestBid->amount + 0.01 : $auction->start_price;

        // Validate bid amount against highest bid
        if ($request->amount < $minimumBid) {
            throw ValidationException::withMessages([
                'amount' => ["Bid must be at least {$minimumBid}."],
            ]);
        }

        $wallet = $request->user()->wallet;
        if (!$wallet) {
            throw ValidationException::withMessages([
                'wallet' => ['You need to have a wallet to place bids.']
            ]);
        }

        if (!$wallet->canWithdraw($request->amount)) {
            throw ValidationException::withMessages([
                'amount' => ['Insufficient funds in your wallet.']
            ]);
        }

        DB::transaction(function () use ($request, $auction, $wallet) {
            // Create the bid
            $bid = $auction->bids()->create([
                'user_id' => $request->user()->id,
                'amount' => $request->amount,
            ]);

            // Hold the bid amount in wallet
            $wallet->withdraw(
                $request->amount,
                "Bid placed on auction: {$auction->title}",
                null,
                $auction
            );

            // Refund previous highest bidder if exists
            $previousHighestBid = $auction->bids()
                ->where('id', '!=', $bid->id)
                ->orderBy('amount', 'desc')
                ->first();

            if ($previousHighestBid) {
                $previousBidder = $previousHighestBid->user;
                $previousBidder->wallet->deposit(
                    $previousHighestBid->amount,
                    "Refund for outbid on auction: {$auction->title}",
                    null,
                    $auction
                );
            }
        });

        // Add this to the store method after creating a bid
        $auction->user->notify(new NewBidNotification($bid));

        // Notify users who are watching this auction
        $auction->watchers->each(function ($watcher) use ($bid) {
            if ($watcher->id !== $bid->user_id) {
                $watcher->notify(new NewBidNotification($bid));
            }
        });

        return response()->json($bid->load('user'), 201);
    }

    public function userBids(User $user)
    {
        return response()->json(
            $user->bids()
                ->with(['auction' => function ($query) {
                    $query->with(['category', 'images']);
                }])
                ->latest()
                ->paginate()
        );
    }
} 