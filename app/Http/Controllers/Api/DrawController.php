<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Draw;
use App\Models\Auction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\DrawResource;

class DrawController extends Controller
{
    public function index()
    {
        $draws = Draw::with(['auction', 'winner'])
            ->where('status', 'active')
            ->latest()
            ->paginate();

        return DrawResource::collection($draws);
    }

    public function show(Draw $draw)
    {
        return new DrawResource($draw->load(['auction', 'winner', 'entries.user']));
    }

    public function store(Request $request, Auction $auction)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'entry_fee' => 'required|numeric|min:0.01',
            'max_entries' => 'nullable|integer|min:1',
            'draw_date' => 'required|date|after:now'
        ]);

        $draw = Draw::create([
            'auction_id' => $auction->id,
            'entry_fee' => $validated['entry_fee'],
            'max_entries' => $validated['max_entries'],
            'draw_date' => $validated['draw_date']
        ]);

        return new DrawResource($draw);
    }

    public function enter(Request $request, Draw $draw)
    {
        if (!$draw->canEnter($request->user())) {
            return response()->json([
                'message' => 'You cannot enter this draw.'
            ], 403);
        }

        $wallet = $request->user()->wallet;
        if (!$wallet || !$wallet->canWithdraw($draw->entry_fee)) {
            return response()->json([
                'message' => 'Insufficient funds in wallet.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($draw, $request, $wallet) {
                // Process payment
                $transaction = $wallet->withdraw(
                    $draw->entry_fee,
                    "Entry fee for draw in auction: {$draw->auction->title}",
                    null,
                    $draw->auction
                );

                // Create entry
                $draw->entries()->create([
                    'user_id' => $request->user()->id,
                    'wallet_transaction_id' => $transaction->id
                ]);
            });

            return response()->json([
                'message' => 'Successfully entered the draw',
                'wallet_balance' => $wallet->balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to enter draw'
            ], 500);
        }
    }

    public function selectWinner(Draw $draw)
    {
        $this->authorize('admin');

        if ($draw->status !== 'active') {
            return response()->json([
                'message' => 'This draw is not active'
            ], 403);
        }

        if ($draw->draw_date > now()) {
            return response()->json([
                'message' => 'Draw date has not arrived yet'
            ], 403);
        }

        try {
            DB::transaction(function () use ($draw) {
                // Randomly select winner
                $winner = $draw->entries()->inRandomOrder()->first()->user;
                
                $draw->update([
                    'winner_id' => $winner->id,
                    'status' => 'completed'
                ]);

                // You could implement notifications here
            });

            return response()->json([
                'message' => 'Winner selected successfully',
                'draw' => new DrawResource($draw->load(['winner', 'auction']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to select winner'
            ], 500);
        }
    }
} 