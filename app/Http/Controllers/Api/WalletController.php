<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = $request->user()->wallet;
        if (!$wallet) {
            $wallet = $request->user()->createWallet();
        }

        return response()->json([
            'balance' => $wallet->balance,
            'recent_transactions' => $wallet->transactions()
                ->with(['auction', 'processor'])
                ->latest()
                ->take(10)
                ->get()
        ]);
    }

    public function adminRecharge(Request $request, User $user)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255'
        ]);

        $wallet = $user->wallet ?? $user->createWallet();

        DB::transaction(function () use ($wallet, $validated, $request) {
            $wallet->deposit(
                $validated['amount'],
                $validated['description'],
                $request->user()
            );
        });

        return response()->json([
            'message' => 'Wallet recharged successfully',
            'new_balance' => $wallet->balance
        ]);
    }
} 