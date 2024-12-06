<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AutoBid extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'auction_id',
        'max_amount',
        'bid_increment',
        'active'
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'bid_increment' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }
} 