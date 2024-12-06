<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function deposit(float $amount, string $description, ?User $processedBy = null, ?Auction $auction = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'amount' => $amount,
            'type' => 'deposit',
            'description' => $description,
            'processed_by' => $processedBy?->id,
            'related_auction_id' => $auction?->id
        ]);
    }

    public function withdraw(float $amount, string $description, ?User $processedBy = null, ?Auction $auction = null): WalletTransaction
    {
        if (!$this->canWithdraw($amount)) {
            throw new \Exception('Insufficient funds');
        }

        $this->balance -= $amount;
        $this->save();

        return $this->transactions()->create([
            'amount' => -$amount,
            'type' => 'withdraw',
            'description' => $description,
            'processed_by' => $processedBy?->id,
            'related_auction_id' => $auction?->id
        ]);
    }
} 