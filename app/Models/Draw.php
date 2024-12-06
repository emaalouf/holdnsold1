<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Draw extends Model
{
    use HasFactory;

    protected $fillable = [
        'auction_id',
        'entry_fee',
        'max_entries',
        'draw_date',
        'winner_id',
        'status'
    ];

    protected $casts = [
        'entry_fee' => 'decimal:2',
        'draw_date' => 'datetime',
    ];

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function entries()
    {
        return $this->hasMany(DrawEntry::class);
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'draw_entries');
    }

    public function canEnter(User $user): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->max_entries && $this->entries()->count() >= $this->max_entries) {
            return false;
        }

        return !$this->entries()->where('user_id', $user->id)->exists();
    }
} 