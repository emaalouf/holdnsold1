<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function checkout(Request $request, Auction $auction)
    {
        // Validate that the auction has ended and the user is the winner
        if ($auction->status !== 'ended') {
            throw ValidationException::withMessages([
                'auction' => ['This auction has not ended yet.'],
            ]);
        }

        if ($auction->winner_user_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'auction' => ['You are not the winner of this auction.'],
            ]);
        }

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        // Here you would integrate with your payment gateway
        // This is a simplified example
        $payment = Payment::create([
            'auction_id' => $auction->id,
            'user_id' => $request->user()->id,
            'amount' => $auction->bids()->max('amount'),
            'payment_method' => $request->payment_method_id,
            'status' => 'pending',
        ]);

        // Process payment with your payment gateway
        try {
            // Payment gateway integration code here
            $payment->update(['status' => 'completed']);
            
            return response()->json($payment);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            
            throw ValidationException::withMessages([
                'payment' => ['Payment processing failed.'],
            ]);
        }
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load(['auction', 'user']));
    }
} 