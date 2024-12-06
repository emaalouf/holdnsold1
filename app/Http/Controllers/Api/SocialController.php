<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\SocialShare;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SocialController extends Controller
{
    protected $platforms = ['facebook', 'twitter', 'whatsapp', 'telegram', 'email'];
    
    public function share(Request $request, Auction $auction, string $platform)
    {
        if (!in_array($platform, $this->platforms)) {
            return response()->json(['message' => 'Invalid platform'], 400);
        }

        // Track the share
        if ($request->user()) {
            SocialShare::create([
                'user_id' => $request->user()->id,
                'auction_id' => $auction->id,
                'platform' => $platform
            ]);
        }

        $shareUrl = $this->generateShareUrl($auction, $platform);

        return response()->json([
            'share_url' => $shareUrl,
            'platform' => $platform
        ]);
    }

    public function shareStats(Auction $auction)
    {
        $stats = [
            'total_shares' => SocialShare::where('auction_id', $auction->id)->count(),
            'platform_breakdown' => SocialShare::where('auction_id', $auction->id)
                ->select('platform', \DB::raw('count(*) as count'))
                ->groupBy('platform')
                ->get()
                ->pluck('count', 'platform')
                ->toArray()
        ];

        return response()->json($stats);
    }

    protected function generateShareUrl(Auction $auction, string $platform): string
    {
        $title = urlencode($auction->title);
        $url = urlencode(config('app.url') . '/auctions/' . $auction->id);
        $description = urlencode(Str::limit($auction->description, 100));

        switch ($platform) {
            case 'facebook':
                return "https://www.facebook.com/sharer/sharer.php?u={$url}";
            
            case 'twitter':
                return "https://twitter.com/intent/tweet?text={$title}&url={$url}";
            
            case 'whatsapp':
                return "https://wa.me/?text={$title}%20{$url}";
            
            case 'telegram':
                return "https://t.me/share/url?url={$url}&text={$title}";
            
            case 'email':
                $subject = urlencode("Check out this auction: {$auction->title}");
                $body = urlencode("I found this interesting auction:\n\n{$auction->title}\n\n{$auction->description}\n\nCheck it out here: " . config('app.url') . '/auctions/' . $auction->id);
                return "mailto:?subject={$subject}&body={$body}";
            
            default:
                return '';
        }
    }
} 