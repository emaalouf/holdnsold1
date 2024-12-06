<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuctionAnalyticsController extends Controller
{
    public function show(Auction $auction)
    {
        $this->authorize('view', $auction);

        $bidStats = $auction->bids()
            ->select(
                DB::raw('COUNT(*) as total_bids'),
                DB::raw('COUNT(DISTINCT user_id) as unique_bidders'),
                DB::raw('MAX(amount) as highest_bid'),
                DB::raw('MIN(amount) as lowest_bid'),
                DB::raw('AVG(amount) as average_bid')
            )
            ->first();

        $bidHistory = $auction->bids()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as bids_count'),
                DB::raw('MAX(amount) as highest_bid')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topBidders = $auction->bids()
            ->select('user_id', DB::raw('COUNT(*) as bids_count'))
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('bids_count')
            ->limit(5)
            ->get();

        $viewsData = $this->getViewsData($auction);

        return response()->json([
            'auction_id' => $auction->id,
            'title' => $auction->title,
            'duration' => [
                'start_time' => $auction->start_time,
                'end_time' => $auction->end_time,
                'duration_hours' => $auction->start_time->diffInHours($auction->end_time),
            ],
            'bid_statistics' => [
                'total_bids' => $bidStats->total_bids,
                'unique_bidders' => $bidStats->unique_bidders,
                'highest_bid' => $bidStats->highest_bid,
                'lowest_bid' => $bidStats->lowest_bid,
                'average_bid' => round($bidStats->average_bid, 2),
                'bid_increment' => $bidStats->highest_bid - $auction->start_price,
            ],
            'engagement' => [
                'views' => $viewsData['total_views'],
                'unique_viewers' => $viewsData['unique_viewers'],
                'watchers_count' => $auction->watchers()->count(),
                'conversion_rate' => $bidStats->unique_bidders > 0 ? 
                    round(($bidStats->unique_bidders / $viewsData['unique_viewers']) * 100, 2) : 0,
            ],
            'bid_history' => $bidHistory,
            'top_bidders' => $topBidders,
            'time_based_analytics' => [
                'peak_bidding_hours' => $this->getPeakBiddingHours($auction),
                'time_to_first_bid' => $this->getTimeToFirstBid($auction),
            ],
        ]);
    }

    public function sellerStats(Request $request)
    {
        $user = $request->user();
        $timeFrame = $request->get('time_frame', 'month'); // day, week, month, year

        $dateColumn = DB::raw('DATE(created_at)');
        switch ($timeFrame) {
            case 'year':
                $dateColumn = DB::raw('YEAR(created_at)');
                break;
            case 'month':
                $dateColumn = DB::raw('DATE_FORMAT(created_at, "%Y-%m")');
                break;
            case 'week':
                $dateColumn = DB::raw('YEARWEEK(created_at)');
                break;
        }

        $auctionStats = $user->auctions()
            ->select(
                $dateColumn . ' as date',
                DB::raw('COUNT(*) as total_auctions'),
                DB::raw('SUM(CASE WHEN status = "ended" AND winner_user_id IS NOT NULL THEN 1 ELSE 0 END) as successful_auctions'),
                DB::raw('COUNT(DISTINCT CASE WHEN bids.id IS NOT NULL THEN bids.user_id END) as unique_bidders')
            )
            ->leftJoin('bids', 'auctions.id', '=', 'bids.auction_id')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = $user->auctions()
            ->whereHas('bids', function ($query) {
                $query->whereColumn('amount', function ($query) {
                    $query->select('amount')
                        ->from('bids as b')
                        ->whereColumn('b.auction_id', 'auctions.id')
                        ->orderByDesc('amount')
                        ->limit(1);
                });
            })
            ->sum('amount');

        $categoryPerformance = $user->auctions()
            ->select(
                'categories.name',
                DB::raw('COUNT(*) as total_auctions'),
                DB::raw('AVG(CASE WHEN auctions.status = "ended" AND winner_user_id IS NOT NULL THEN 1 ELSE 0 END) as success_rate'),
                DB::raw('AVG(CASE WHEN bids.id IS NOT NULL THEN bids.amount END) as average_winning_bid')
            )
            ->join('categories', 'auctions.category_id', '=', 'categories.id')
            ->leftJoin('bids', function ($join) {
                $join->on('auctions.id', '=', 'bids.auction_id')
                    ->whereColumn('bids.amount', function ($query) {
                        $query->select('amount')
                            ->from('bids as b')
                            ->whereColumn('b.auction_id', 'auctions.id')
                            ->orderByDesc('amount')
                            ->limit(1);
                    });
            })
            ->groupBy('categories.id', 'categories.name')
            ->get();

        return response()->json([
            'summary' => [
                'total_auctions' => $user->auctions()->count(),
                'active_auctions' => $user->auctions()->where('status', 'active')->count(),
                'successful_auctions' => $user->auctions()->whereNotNull('winner_user_id')->count(),
                'total_revenue' => $totalRevenue,
                'average_success_rate' => $categoryPerformance->avg('success_rate'),
            ],
            'timeline' => $auctionStats,
            'category_performance' => $categoryPerformance,
            'buyer_retention' => $this->getBuyerRetentionStats($user),
        ]);
    }

    private function getViewsData(Auction $auction)
    {
        // This would typically come from your view tracking system
        // For now, we'll return dummy data
        return [
            'total_views' => rand(100, 1000),
            'unique_viewers' => rand(50, 200),
        ];
    }

    private function getPeakBiddingHours(Auction $auction)
    {
        return $auction->bids()
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as bids_count')
            )
            ->groupBy('hour')
            ->orderByDesc('bids_count')
            ->limit(5)
            ->get();
    }

    private function getTimeToFirstBid(Auction $auction)
    {
        $firstBid = $auction->bids()->orderBy('created_at')->first();
        
        return $firstBid ? 
            $auction->start_time->diffInMinutes($firstBid->created_at) : 
            null;
    }

    private function getBuyerRetentionStats($user)
    {
        return DB::table('bids')
            ->join('auctions', 'bids.auction_id', '=', 'auctions.id')
            ->where('auctions.user_id', $user->id)
            ->select('bids.user_id')
            ->groupBy('bids.user_id')
            ->select(
                'bids.user_id',
                DB::raw('COUNT(DISTINCT auctions.id) as auctions_participated')
            )
            ->having('auctions_participated', '>', 1)
            ->orderByDesc('auctions_participated')
            ->limit(10)
            ->get();
    }
} 