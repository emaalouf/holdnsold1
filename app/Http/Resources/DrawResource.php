<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DrawResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'auction' => new AuctionResource($this->whenLoaded('auction')),
            'entry_fee' => $this->entry_fee,
            'max_entries' => $this->max_entries,
            'current_entries' => $this->entries()->count(),
            'draw_date' => $this->draw_date,
            'status' => $this->status,
            'winner' => new UserResource($this->whenLoaded('winner')),
            'entries' => DrawEntryResource::collection($this->whenLoaded('entries')),
            'can_enter' => $request->user() ? $this->canEnter($request->user()) : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 