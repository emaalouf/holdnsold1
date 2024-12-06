<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        $currentUser = auth()->user();
        
        return [
            'id' => $this->id,
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'auction' => new AuctionResource($this->whenLoaded('auction')),
            'unread_count' => $this->unreadCount($currentUser),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 