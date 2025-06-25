<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Resource;
use App\Models\Upvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;

class CommentController extends Controller
{
    /**
     * Check if a user has upvoted a specific comment
     */
    protected function isUpvotedByUser($comment, $userId)
    {
        return Upvote::where([
            'user_id' => $userId,
            'upvoteable_id' => $comment->id,
            'upvoteable_type' => Comment::class,
        ])->exists();
    }
    /**
     * Get comments for a specific resource
     */
    public function getResourceComments($resourceId)
    {
        $user = Auth::user();
        $resource = Resource::findOrFail($resourceId);
        
        $comments = $resource->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $comments = $comments->map(function ($comment) use ($user) {
            // Add upvote information
            $comment->upvote_count = $comment->upvotes()->count();
            $comment->is_upvoted = $this->isUpvotedByUser($comment, $user->id);
            return $comment;
        });
        
        return response()->json([
            'comments' => $comments,
            'count' => $comments->count()
        ]);
    }
    
    /**
     * Add a comment to a resource
     */
    public function addResourceComment(Request $request, $resourceId)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);
        
        $resource = Resource::findOrFail($resourceId);
        $user = Auth::user();
        
        $comment = new Comment([
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'commentable_type' => Resource::class,
            'commentable_id' => $resource->id,
        ]);
        
        $comment->save();
        // Notify resource owner about the new comment
        $resourceOwner = $resource->user;
        if ($resourceOwner && $resourceOwner->id !== $user->id) {
            Notification::create([
                'user_id'   => $resourceOwner->id,
                'sender_id' => $user->id,
                'type'      => 'resource_comment',
                'data'      => [
                    'resource_id'   => $resource->id,
                    'comment_id'    => $comment->id,
                    'resource_title'=> $resource->title,
                    'message'       => $user->first_name.' commented on your resource "'.$resource->title.'"',
                    'url'           => url('/resources/'.$resource->id),
                ],
            ]);
        }
        
        // Load the user relationship for the response
        $comment->load('user');
        
        // Add upvote information
        $comment->upvote_count = 0; // New comment, no upvotes yet
        $comment->is_upvoted = false;
        
        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment,
            'comment_count' => $resource->comments()->count()
        ], 201);
    }
    
    /**
     * Update a comment
     */
    public function updateComment(Request $request, $commentId)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);
        
        $user = Auth::user();
        $comment = Comment::findOrFail($commentId);
        
        // Check if the user is the owner of the comment
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this comment'
            ], 403);
        }
        
        $comment->update([
            'body' => $request->input('body')
        ]);
        
        $comment->load('user');
        
        // Add upvote information
        $comment->upvote_count = $comment->upvotes()->count();
        $comment->is_upvoted = $this->isUpvotedByUser($comment, $user->id);
        
        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment
        ]);
    }
    
    /**
     * Delete a comment
     */
    public function deleteComment($commentId)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($commentId);
        
        // Check if the user is the owner of the comment
        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to delete this comment'
            ], 403);
        }
        
        // Store the commentable_id and commentable_type before deleting
        $commentableId = $comment->commentable_id;
        $commentableType = $comment->commentable_type;
        
        $comment->delete();
        
        // Get the new comment count for the parent resource
        $commentCount = Comment::where([
            'commentable_id' => $commentableId,
            'commentable_type' => $commentableType,
        ])->count();
        
        return response()->json([
            'message' => 'Comment deleted successfully',
            'comment_count' => $commentCount
        ]);
    }
    
    /**
     * Toggle upvote for a comment
     */
    public function toggleUpvote($commentId)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($commentId);
        
        // Check if the user has already upvoted this comment
        $existingUpvote = Upvote::where([
            'user_id' => $user->id,
            'upvoteable_id' => $comment->id,
            'upvoteable_type' => Comment::class,
        ])->first();
        
        if ($existingUpvote) {
            // User has already upvoted, so remove the upvote
            $existingUpvote->delete();
            
            return response()->json([
                'message' => 'Upvote removed successfully',
                'upvoted' => false,
                'upvote_count' => $comment->upvotes()->count()
            ]);
        } else {
            // User hasn't upvoted, so add an upvote
            $upvote = new Upvote([
                'user_id' => $user->id,
                'upvoteable_id' => $comment->id,
                'upvoteable_type' => Comment::class,
            ]);
            
            $upvote->save();
            
            return response()->json([
                'message' => 'Comment upvoted successfully',
                'upvoted' => true,
                'upvote_count' => $comment->upvotes()->count()
            ], 201);
        }
    }
}
