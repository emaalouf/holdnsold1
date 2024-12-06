<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DrawEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'draw_id',
        'user_id',
        'wallet_transaction_id'
    ];

    public function draw()
    {
        return $this->belongsTo(Draw::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }
} 