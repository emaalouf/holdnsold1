<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'description',
        'related_auction_id',
        'processed_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class, 'related_auction_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
} 