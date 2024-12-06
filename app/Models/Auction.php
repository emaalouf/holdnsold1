<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'start_price',
        'start_time',
        'end_time',
        'status',
        'winner_user_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function images()
    {
        return $this->hasMany(AuctionImage::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function highestBid()
    {
        return $this->bids()->orderBy('amount', 'desc')->first();
    }

    /**
     * Get the users watching this auction.
     */
    public function watchers()
    {
        return $this->belongsToMany(User::class, 'user_watchlist')
                    ->withTimestamps();
    }

    /**
     * Check if the auction is being watched by a specific user.
     */
    public function isWatchedBy(User $user)
    {
        return $this->watchers()->where('user_id', $user->id)->exists();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function autoBids()
    {
        return $this->hasMany(AutoBid::class);
    }

    public function shares()
    {
        return $this->hasMany(SocialShare::class);
    }

    public function getShareCountAttribute()
    {
        return $this->shares()->count();
    }
} 