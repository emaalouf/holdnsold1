<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function delete(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id || 
               $user->isAdmin() || 
               $user->id === $comment->auction->user_id;
    }
} 