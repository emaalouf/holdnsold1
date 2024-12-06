<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Events\NewMessage;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $conversations = $request->user()
            ->conversations()
            ->with(['participants', 'lastMessage', 'auction'])
            ->latest('updated_at')
            ->paginate();

        return ConversationResource::collection($conversations);
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $conversation->load(['participants', 'messages.user', 'auction']);

        // Mark messages as read
        $conversation->participants()
            ->where('user_id', auth()->id())
            ->update(['last_read_at' => now()]);

        return new ConversationResource($conversation);
    }

    public function store(Request $request, User $recipient)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'auction_id' => 'nullable|exists:auctions,id'
        ]);

        // Check if conversation exists between these users
        $conversation = Conversation::whereHas('participants', function ($query) use ($recipient) {
            $query->where('user_id', auth()->id());
        })->whereHas('participants', function ($query) use ($recipient) {
            $query->where('user_id', $recipient->id);
        })->where('auction_id', $request->auction_id)
        ->first();

        // If no conversation exists, create one
        if (!$conversation) {
            $conversation = Conversation::create([
                'auction_id' => $request->auction_id
            ]);

            $conversation->participants()->attach([
                auth()->id() => ['last_read_at' => now()],
                $recipient->id => ['last_read_at' => null]
            ]);
        }

        // Create the message
        $message = $conversation->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content']
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Broadcast the new message
        broadcast(new NewMessage($message))->toOthers();

        return new MessageResource($message->load('user'));
    }

    public function destroy(Message $message)
    {
        $this->authorize('delete', $message);
        
        $message->delete();
        
        return response()->json(['message' => 'Message deleted successfully']);
    }
} 