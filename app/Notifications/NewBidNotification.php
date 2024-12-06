<?php

namespace App\Notifications;

use App\Models\Bid;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewBidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $bid;

    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'bid_id' => $this->bid->id,
            'auction_id' => $this->bid->auction_id,
            'bidder_name' => $this->bid->user->name,
            'amount' => $this->bid->amount,
            'message' => "New bid of {$this->bid->amount} placed on your auction {$this->bid->auction->title}"
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'new_bid',
            'data' => $this->toDatabase($notifiable)
        ]);
    }
} 