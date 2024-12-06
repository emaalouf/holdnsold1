<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['auction_id'];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_user')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function auction()
    {
        return $this->belongsTo(Auction::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function unreadCount(User $user)
    {
        $lastRead = $this->participants()
            ->where('user_id', $user->id)
            ->first()
            ->pivot
            ->last_read_at;

        return $this->messages()
            ->where('created_at', '>', $lastRead ?? '1970-01-01')
            ->where('user_id', '!=', $user->id)
            ->count();
    }
} 