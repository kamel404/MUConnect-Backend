<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Attachment;
use App\Models\SavedItem;
use App\Models\Upvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreResourceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateResourceRequest;
class ResourceController extends Controller
{

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get pagination parameters from request
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $page = $request->input('page', 1); // Default to first page
        
        // Create paginated query
        $resourcesQuery = Resource::with(['attachments', 'user', 'course', 'polls' => function($query) {
            $query->with('options');
        }])->latest();
        
        // Get paginated results
        $paginatedResources = $resourcesQuery->paginate($perPage);
        
        // Transform resources with additional data
        $resources = $paginatedResources->getCollection()->map(function ($resource) use ($user) {
            // Add upvote information
            $resource->upvote_count = $resource->upvotes()->count();
            $resource->is_upvoted = $resource->isUpvotedByUser($user->id);
            
            // Add comment count
            $resource->comment_count = $resource->comments()->count();
            
            // Add saved status
            $resource->is_saved = $resource->savedBy()->where('user_id', $user->id)->exists();

            // Add poll voting information (user's selected option)
            if ($resource->polls) {
                $poll = $resource->polls;
                $optionIds = $poll->options->pluck('id');

                $userVote = Upvote::where('user_id', $user->id)
                    ->where('upvoteable_type', \App\Models\PollOption::class)
                    ->whereIn('upvoteable_id', $optionIds)
                    ->first();

                $poll->user_option_id = $userVote?->upvoteable_id;
                $poll->options = $poll->options->map(function ($opt) use ($userVote) {
                    $opt->is_selected = $userVote && $userVote->upvoteable_id === $opt->id;
                    return $opt;
                });
            }
            
            return $resource;
        });
        
        // Create custom response with pagination metadata
        return response()->json([
            'data' => $resources,
            'pagination' => [
                'total' => $paginatedResources->total(),
                'per_page' => $paginatedResources->perPage(),
                'current_page' => $paginatedResources->currentPage(),
                'last_page' => $paginatedResources->lastPage(),
                'next_page_url' => $paginatedResources->nextPageUrl(),
                'prev_page_url' => $paginatedResources->previousPageUrl(),
                'from' => $paginatedResources->firstItem(),
                'to' => $paginatedResources->lastItem(),
            ]
        ]);
    }


    public function show($id)
    {
        $user = Auth::user();
        $resource = Resource::with(['attachments', 'user', 'course','comments.user', 'polls' => function($query) {
            $query->with('options');
        }])->find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }
        
        // Add upvote information
        $resource->upvote_count = $resource->upvotes()->count();
        $resource->is_upvoted = $resource->isUpvotedByUser($user->id);
        
        // Add comment count
        $resource->comment_count = $resource->comments()->count();
        
        // Add course info
            $resource->course_info = $resource->course;
            // Add course title
        $resource->course_title = optional($resource->course)->title;
        // Add saved status
        $resource->is_saved = $resource->savedBy()->where('user_id', $user->id)->exists();

        // Add poll voting information (user's selected option)
        if ($resource->polls) {
            $poll           = $resource->polls;
            $optionIds      = $poll->options->pluck('id');
            $userVote       = Upvote::where('user_id', $user->id)
                ->where('upvoteable_type', \App\Models\PollOption::class)
                ->whereIn('upvoteable_id', $optionIds)
                ->first();

            $poll->user_option_id = $userVote?->upvoteable_id;

            // Mark each option with `is_selected`
            $poll->options = $poll->options->map(function ($opt) use ($userVote) {
                $opt->is_selected = $userVote && $userVote->upvoteable_id === $opt->id;
                return $opt;
            });
        }

        // Add upvote info to each comment
        $resource->comments = $resource->comments->map(function ($comment) use ($user) {
            $comment->upvote_count = $comment->upvotes()->count();
            $comment->is_upvoted   = Upvote::where([
                'user_id'        => $user->id,
                'upvoteable_id'  => $comment->id,
                'upvoteable_type'=> \App\Models\Comment::class,
            ])->exists();
            return $comment;
        });

        return response()->json($resource);
    }

    /**
     * Toggle save status for a resource
     * 
     * If the resource is already saved, it will be unsaved
     * If the resource is not saved, it will be saved
     * 
     * @param int $id The resource ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSave($id)
    {
        $user = Auth::user();
        $resource = Resource::findOrFail($id);

        // Check if the resource is already saved by this user
        $savedItem = SavedItem::where([
            'user_id' => $user->id,
            'saveable_id' => $resource->id,
            'saveable_type' => Resource::class,
        ])->first();
        
        // If already saved, unsave it
        if ($savedItem) {
            $savedItem->delete();
            return response()->json([
                'message' => 'Resource unsaved successfully',
                'saved' => false
            ]);
        } 
        // If not saved, save it
        else {
            $saved = SavedItem::create([
                'user_id' => $user->id,
                'saveable_id' => $resource->id,
                'saveable_type' => Resource::class,
            ]);
            
            return response()->json([
                'message' => 'Resource saved successfully',
                'saved' => true,
                'item' => $saved
            ], 201);
        }
    }
    
    /**
     * Toggle upvote for a resource
     * 
     * If the user has already upvoted the resource, remove the upvote
     * Otherwise, add an upvote
     */
    public function toggleUpvote($id)
    {
        $user = Auth::user();
        $resource = Resource::findOrFail($id);
        
        // Check if the user has already upvoted this resource
        $existingUpvote = \App\Models\Upvote::where([
            'user_id' => $user->id,
            'upvoteable_id' => $resource->id,
            'upvoteable_type' => Resource::class,
        ])->first();
        
        if ($existingUpvote) {
            // User has already upvoted, so remove the upvote
            $existingUpvote->delete();
            
            return response()->json([
                'message' => 'Upvote removed successfully',
                'upvoted' => false,
                'upvote_count' => $resource->upvotes()->count()
            ]);
        } else {
            // User hasn't upvoted, so add an upvote
            $upvote = new \App\Models\Upvote([
                'user_id' => $user->id,
                'upvoteable_id' => $resource->id,
                'upvoteable_type' => Resource::class,
            ]);
            
            $upvote->save();
            
            return response()->json([
                'message' => 'Resource upvoted successfully',
                'upvoted' => true,
                'upvote_count' => $resource->upvotes()->count()
            ], 201);
        }
    }


    public function storeTest(StoreResourceRequest $request)
    {
        $user = Auth::user();
        DB::beginTransaction();

        try {
            $attachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $checksum = hash_file('sha256', $file->getRealPath());

                    $existing = Attachment::where('checksum', $checksum)->first();

                    if ($existing) {
                        $attachments[] = $existing;
                        continue;
                    }

                    $mimeType = $file->getMimeType();
                    $fileType = explode('/', $mimeType)[0];

                    $enumType = match ($fileType) {
                        'image' => 'images',
                        'video' => 'videos',
                        'application' => 'documents',
                        default => 'others',
                    };

                    $storagePath = "attachments/{$enumType}";
                    $path = $file->store($storagePath, 'public');

                    $newAttachment = Attachment::create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $this->guessFileType($file),
                        'mime_type' => $mimeType,
                        'checksum' => $checksum,

                    ]);

                    $attachments[] = $newAttachment;
                }
            }

            $resource = Resource::create([
                'user_id' => $user->id,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'course_id' => $request->input('course_id'),
                'major_id' => $request->input('major_id'),
                'faculty_id' => $request->input('faculty_id'),
            ]);

            if (!empty($attachments)) {
                $resource->attachments()->attach(collect($attachments)->pluck('id')->toArray());
            }
            
            // Handle poll creation if poll data is provided
            if ($request->has('poll') && is_array($request->input('poll'))) {
                $pollData = $request->input('poll');
                
                if (isset($pollData['question']) && isset($pollData['options']) && is_array($pollData['options'])) {
                    // Create the poll
                    $poll = new \App\Models\Poll([
                        'question' => $pollData['question'],
                    ]);
                    
                    // Associate poll with resource
                    $resource->polls()->save($poll);
                    
                    // Create poll options
                    foreach ($pollData['options'] as $optionText) {
                        $poll->options()->create([
                            'option_text' => $optionText,
                            'vote_count' => 0
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Resource created successfully',
                'resource' => $resource->load(['attachments', 'user', 'polls.options']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating resource',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function updateTest(Request $request, $id)
    {
        $user = Auth::user();
        \Log::info('Update request data:', [
            'all_data' => $request->all(),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'has_files' => $request->hasFile('attachments'),
            'remove_attachments' => $request->input('remove_attachments')
        ]);
        \Log::info('Request title: ' . $request->input('title'));
        \Log::info('Has files: ' . json_encode($request->hasFile('attachments')));

        DB::beginTransaction();
        try {
            // Find the resource
            $resource = Resource::where('id', $id);

            // If user is not admin or moderator, restrict to own resources
            if (!$user->hasRole(['admin', 'moderator'])) {
                $resource = $resource->where('user_id', $user->id);
            }

            $resource = $resource->firstOrFail();

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $checksum = hash_file('sha256', $file->getRealPath());
                    $existing = Attachment::where('checksum', $checksum)->first();
                    if ($existing) {
                        $attachments[] = $existing;
                        continue;
                    }
                    $mimeType = $file->getMimeType();
                    $fileType = explode('/', $mimeType)[0];
                    $enumType = match ($fileType) {
                        'image' => 'images',
                        'video' => 'videos',
                        'application' => 'documents',
                        default => 'others',
                    };
                    $storagePath = "attachments/{$enumType}";
                    $path = $file->store($storagePath, 'public');
                    $newAttachment = Attachment::create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $this->guessFileType($file),
                        'mime_type' => $mimeType,
                        'checksum' => $checksum,
                    ]);
                    $attachments[] = $newAttachment;
                }
            }

            $updateData = [];
            if ($request->has('title')) {
                $updateData['title'] = $request->input('title');
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->input('description');
            }
            if (!empty($updateData)) {
                $resource->update($updateData);
            }

            // Handle attachment removal
            if ($request->has('remove_attachments') && is_array($request->input('remove_attachments'))) {
                $attachmentIdsToRemove = $request->input('remove_attachments');
                // Only detach attachments that belong to this resource
                $resource->attachments()->detach($attachmentIdsToRemove);
            }

            // Handle adding new attachments
            if (!empty($attachments)) {
                $resource->attachments()->attach(collect($attachments)->pluck('id')->toArray());
            }
            
            // Handle poll updates if poll data is provided
            if ($request->has('poll') && is_array($request->input('poll'))) {
                $pollData = $request->input('poll');
                
                // Get existing poll or create a new one
                $poll = $resource->polls;
                
                if (!$poll && isset($pollData['question'])) {
                    // Create new poll if it doesn't exist
                    $poll = new \App\Models\Poll([
                        'question' => $pollData['question'],
                    ]);
                    $resource->polls()->save($poll);
                } elseif ($poll && isset($pollData['question'])) {
                    // Update existing poll
                    $poll->question = $pollData['question'];
                    $poll->save();
                    
                    // Delete existing options if we're replacing them
                    if (isset($pollData['options']) && is_array($pollData['options'])) {
                        $poll->options()->delete();
                    }
                }
                
                // Create or update poll options
                if ($poll && isset($pollData['options']) && is_array($pollData['options'])) {
                    foreach ($pollData['options'] as $optionText) {
                        $poll->options()->create([
                            'option_text' => $optionText,
                            'vote_count' => 0
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Resource updated successfully',
                'resource' => $resource->load(['attachments', 'polls.options']),
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating resource',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Helper to guess file type from MIME
    protected function guessFileType($file)
    {
        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        return 'document';
    }
    
    /**
     * Vote on a poll option
     *
     * @param int $optionId The poll option ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function votePollOption($optionId)
    {
        $user = Auth::user();

        try {
            $option = \App\Models\PollOption::findOrFail($optionId);
            $poll   = $option->poll;

            // Ensure option belongs to a valid poll & resource
            if (!$poll || !$poll->resource) {
                return response()->json(['message' => 'Invalid poll option'], 400);
            }

            // Retrieve existing vote (if any) by this user on this poll
            $existingVote = Upvote::where('user_id', $user->id)
                ->where('upvoteable_type', \App\Models\PollOption::class)
                ->whereIn('upvoteable_id', $poll->options()->pluck('id'))
                ->first();

            $action = 'added';

            DB::transaction(function () use ($existingVote, $option, $user, &$action) {
                if ($existingVote) {
                    if ($existingVote->upvoteable_id === $option->id) {
                        // User clicked same option → remove vote
                        $option->decrement('vote_count');
                        $existingVote->delete();
                        $action = 'removed';
                    } else {
                        // Switch vote to another option
                        \App\Models\PollOption::where('id', $existingVote->upvoteable_id)->decrement('vote_count');
                        $existingVote->upvoteable_id = $option->id;
                        $existingVote->save();
                        $option->increment('vote_count');
                        $action = 'switched';
                    }
                } else {
                    // Fresh vote
                    Upvote::create([
                        'user_id'        => $user->id,
                        'upvoteable_type'=> \App\Models\PollOption::class,
                        'upvoteable_id'  => $option->id,
                    ]);
                    $option->increment('vote_count');
                }
            });

            $message = match($action) {
                'removed'  => 'Vote removed successfully',
                'switched' => 'Vote switched successfully',
                default    => 'Vote recorded successfully',
            };

            return response()->json([
                'message' => $message,
                'poll'    => $poll->load('options')
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Poll option not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error recording vote',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroyTest($id)
    {//todo write a clean code that would return the error message if the user is not authorized to delete this 
        $user = Auth::user();
        // Ensure the user is authorized to delete this resource
        $resource = Resource::with('attachments')->find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        // Check if the user is authorized to delete this resource
        // Allow if user is the resource owner OR has admin/moderator role
        if ($resource->user_id !== $user->id && !$user->hasRole(['admin', 'moderator'])) {
            return response()->json(['message' => 'Unauthorized to delete this resource'], 403);
        }

        DB::beginTransaction();
        try {
            //$resource = Resource::with('attachments')->findOrFail($id);
            $attachments = $resource->attachments;

            // Detach all attachments (pivot cleanup)
            $resource->attachments()->detach();

            // Delete the resource
            $resource->delete();

            // Delete orphaned attachments and files
            foreach ($attachments as $attachment) {
                if ($attachment->resources()->count() === 0) {
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                    $attachment->delete();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Resource and orphaned attachments deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error deleting resource', 'error' => $e->getMessage()], 500);
        }
    }
}
