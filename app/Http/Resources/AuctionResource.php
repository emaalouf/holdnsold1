<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuctionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_price' => $this->start_price,
            'current_price' => $this->bids()->max('amount') ?? $this->start_price,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'seller' => new UserResource($this->whenLoaded('user')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'is_watched' => $this->when(auth()->check(), function () {
                return $this->isWatchedBy(auth()->user());
            }),
            'watchers_count' => $this->watchers()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'share_count' => $this->share_count,
            'share_stats' => $this->when($this->shares()->exists(), function () {
                return $this->shares()
                    ->select('platform', \DB::raw('count(*) as count'))
                    ->groupBy('platform')
                    ->get()
                    ->pluck('count', 'platform');
            }),
        ];
    }
} 