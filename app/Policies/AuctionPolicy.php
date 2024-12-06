<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuctionPolicy
{
    use HandlesAuthorization;

    public function viewAnalytics(User $user, Auction $auction)
    {
        return $user->id === $auction->user_id || $user->isAdmin();
    }
} 