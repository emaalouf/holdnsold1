<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AutoBidResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'auction' => new AuctionResource($this->whenLoaded('auction')),
            'max_amount' => $this->max_amount,
            'bid_increment' => $this->bid_increment,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 