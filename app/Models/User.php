<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function auctions()
    {
        return $this->hasMany(Auction::class);
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Get the auctions that the user is watching.
     */
    public function watchlist()
    {
        return $this->belongsToMany(Auction::class, 'user_watchlist')
                    ->withTimestamps();
    }

    /**
     * Get reviews received by the user
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    /**
     * Get reviews written by the user
     */
    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * Get the user's average rating
     */
    public function averageRating()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function autoBids()
    {
        return $this->hasMany(AutoBid::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function createWallet()
    {
        if (!$this->wallet) {
            return $this->wallet()->create();
        }
        return $this->wallet;
    }
}
