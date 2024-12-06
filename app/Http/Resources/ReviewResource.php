<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'auction' => new AuctionResource($this->whenLoaded('auction')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 