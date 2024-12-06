<?php

namespace App\Notifications;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AuctionEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $auction;

    public function __construct(Auction $auction)
    {
        $this->auction = $auction;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'auction_id' => $this->auction->id,
            'title' => $this->auction->title,
            'message' => "Your watched auction {$this->auction->title} is ending soon!"
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'auction_ending',
            'data' => $this->toDatabase($notifiable)
        ]);
    }
} 