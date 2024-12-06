<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->paginate());
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'sometimes|string|in:user,admin,seller',
            'is_banned' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_auctions' => Auction::count(),
            'active_auctions' => Auction::where('status', 'active')->count(),
            'total_bids' => Bid::count(),
            'recent_auctions' => Auction::with(['user', 'category'])
                                      ->latest()
                                      ->take(5)
                                      ->get(),
            'recent_users' => User::latest()
                                 ->take(5)
                                 ->get(),
            'recent_bids' => Bid::with(['user', 'auction'])
                               ->latest()
                               ->take(5)
                               ->get(),
        ];

        return response()->json($stats);
    }

    public function bulkActionAuctions(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'auction_ids' => 'required|array',
            'auction_ids.*' => 'exists:auctions,id',
            'action' => 'required|string|in:close,delete,feature,unfeature,verify,reject',
            'reason' => 'required_if:action,reject|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $auctions = Auction::whereIn('id', $validated['auction_ids']);
            $count = $auctions->count();

            switch ($validated['action']) {
                case 'close':
                    $auctions->update([
                        'status' => 'ended',
                        'end_time' => now()
                    ]);

                    // Process winners for each auction
                    foreach ($auctions->get() as $auction) {
                        $highestBid = $auction->bids()->orderBy('amount', 'desc')->first();
                        if ($highestBid) {
                            $auction->update(['winner_user_id' => $highestBid->user_id]);
                            // You could trigger notifications here
                        }
                    }
                    break;

                case 'delete':
                    $auctions->delete();
                    break;

                case 'feature':
                    $auctions->update(['is_featured' => true]);
                    break;

                case 'unfeature':
                    $auctions->update(['is_featured' => false]);
                    break;

                case 'verify':
                    $auctions->update(['is_verified' => true]);
                    break;

                case 'reject':
                    $auctions->update([
                        'status' => 'rejected',
                        'rejection_reason' => $validated['reason']
                    ]);
                    // You could trigger notifications to sellers here
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully processed {$count} auctions",
                'action' => $validated['action'],
                'affected_count' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkActionUsers(Request $request)
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|string|in:ban,unban,verify,unverify,promote_to_seller,remove_seller,suspend',
            'duration' => 'required_if:action,suspend|integer|min:1',
            'reason' => 'required_if:action,ban,suspend|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $users = User::whereIn('id', $validated['user_ids'])
                        ->where('id', '!=', auth()->id()); // Prevent self-modification
            
            $count = $users->count();

            switch ($validated['action']) {
                case 'ban':
                    $users->update([
                        'is_banned' => true,
                        'ban_reason' => $validated['reason']
                    ]);
                    break;

                case 'unban':
                    $users->update([
                        'is_banned' => false,
                        'ban_reason' => null
                    ]);
                    break;

                case 'verify':
                    $users->update(['is_verified' => true]);
                    break;

                case 'unverify':
                    $users->update(['is_verified' => false]);
                    break;

                case 'promote_to_seller':
                    $users->update(['role' => 'seller']);
                    break;

                case 'remove_seller':
                    $users->where('role', 'seller')
                         ->update(['role' => 'user']);
                    break;

                case 'suspend':
                    $suspendUntil = now()->addDays($validated['duration']);
                    $users->update([
                        'suspended_until' => $suspendUntil,
                        'suspension_reason' => $validated['reason']
                    ]);
                    break;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully processed {$count} users",
                'action' => $validated['action'],
                'affected_count' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
} 