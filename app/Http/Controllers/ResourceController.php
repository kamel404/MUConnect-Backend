<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Attachment;
use App\Models\SavedItem;
use App\Models\Upvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;
use App\Http\Requests\StoreResourceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateResourceRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ResourceController extends Controller
{
    /**
     * Get top contributors based on resources and upvotes
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topContributors(Request $request)
    {
        // Get pagination parameters from request
        $limit = $request->input('limit', 3); // Default to top 3
        
        // Get users with their resource counts and total upvotes received
        $topContributors = \App\Models\User::with(['faculty', 'major'])
            ->select(['users.*'])
            ->selectRaw('(SELECT COUNT(*) FROM resources WHERE resources.user_id = users.id) as resources_count')
            ->selectSub(function ($query) {
                $query->selectRaw('COUNT(*)')
                    ->from('upvotes')
                    ->whereColumn('upvotes.upvoteable_id', 'users.id')
                    ->where('upvoteable_type', \App\Models\User::class);
            }, 'user_upvote_count')
            ->selectSub(function ($query) {
                $query->selectRaw('COUNT(*)')
                    ->from('resources')
                    ->join('upvotes', function ($join) {
                        $join->on('resources.id', '=', 'upvotes.upvoteable_id')
                            ->where('upvotes.upvoteable_type', \App\Models\Resource::class);
                    })
                    ->whereColumn('resources.user_id', 'users.id');
            }, 'resource_upvote_count')
            // Add total upvotes received (user upvotes + resource upvotes)
            ->withCasts([
                'resources_count' => 'integer',
                'user_upvote_count' => 'integer',
                'resource_upvote_count' => 'integer'
            ])
            // Get only users with resources
            ->whereRaw('(SELECT COUNT(*) FROM resources WHERE resources.user_id = users.id) > 0')
            // Order by resources count and then by upvotes received
            ->orderByRaw('resources_count DESC')
            ->orderByRaw('(user_upvote_count + resource_upvote_count) DESC')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                // Add computed fields
                $user->contribution_score = $user->resources_count + 
                    intval($user->user_upvote_count) + 
                    intval($user->resource_upvote_count);
                    
                // Add faculty and major names if available
                $user->faculty_name = optional($user->faculty)->name;
                $user->major_name = optional($user->major)->name;
                
                return $user;
            });
            
        return response()->json([
            'data' => $topContributors
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get pagination parameters from request
        $perPage = $request->input('per_page', 10); // Default 10 items per page
        $page = $request->input('page', 1); // Default to first page
        
        // Create base query with relationships
        $resourcesQuery = Resource::with(['attachments', 'user', 'course', 'polls' => function($query) {
            $query->with('options');
        }]);
        
        // Apply filters if provided
        // Filter by faculty_id
        if ($request->has('faculty_id')) {
            $resourcesQuery->where('faculty_id', $request->input('faculty_id'));
        }
        
        // Filter by major_id
        if ($request->has('major_id')) {
            $resourcesQuery->where('major_id', $request->input('major_id'));
        }
        
        // Filter by course_id
        if ($request->has('course_id')) {
            $resourcesQuery->where('course_id', $request->input('course_id'));
        }
        
        // Search by title
        if ($request->has('search') && !empty($request->input('search'))) {
            $searchTerm = $request->input('search');
            $resourcesQuery->where(function($query) use ($searchTerm) {
                $query->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        // Order by latest by default
        $resourcesQuery->latest();
        
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
            // Notify resource owner about the upvote
            $resource->load('user');
            $owner = $resource->user;
            if ($owner && $owner->id !== $user->id) {
                Notification::create([
                    'user_id'   => $owner->id,
                    'sender_id' => $user->id,
                    'type'      => 'resource_upvote',
                    'data'      => [
                        'resource_id'    => $resource->id,
                        'resource_title' => $resource->title,
                        'message'        => $user->first_name.' upvoted your resource "'.$resource->title.'"',
                        'url'            => url('/resources/'.$resource->id),
                    ],
                ]);
            }
            
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

    // Add this method to handle secure file uploads
    private function validateAndStoreFile($file, $resourceId = null)
    {
        // Define allowed file types with their signatures
        $allowedTypes = [
            'pdf' => [
                'mime' => 'application/pdf',
                'signature' => ['25504446'] // %PDF
            ],
            'jpg' => [
                'mime' => 'image/jpeg',
                'signature' => ['FFD8FF']
            ],
            'png' => [
                'mime' => 'image/png',
                'signature' => ['89504E47']
            ],
            'doc' => [
                'mime' => 'application/msword',
                'signature' => ['D0CF11E0']
            ],
            'docx' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'signature' => ['504B0304']
            ]
        ];

        // Check file size (max 10MB)
        if ($file->getSize() > 10485760) {
            throw new \InvalidArgumentException('File size exceeds 10MB limit');
        }

        // Get real MIME type using finfo
        $realMimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file->getRealPath());
        
        // Get file signature
        $handle = fopen($file->getRealPath(), 'rb');
        $fileHeader = strtoupper(bin2hex(fread($handle, 4)));
        fclose($handle);

        // Validate file type
        $isValidFile = false;
        $detectedExtension = null;
        
        foreach ($allowedTypes as $ext => $config) {
            if ($realMimeType === $config['mime']) {
                foreach ($config['signature'] as $signature) {
                    if (strpos($fileHeader, $signature) === 0) {
                        $isValidFile = true;
                        $detectedExtension = $ext;
                        break 2;
                    }
                }
            }
        }

        if (!$isValidFile) {
            throw new \InvalidArgumentException('Invalid or potentially malicious file type');
        }

        // Generate secure filename
        $secureFilename = Str::uuid() . '.' . $detectedExtension;
        
        // Store file in private storage
        $path = $file->storeAs(
            'attachments/' . date('Y/m'), 
            $secureFilename, 
            'private'
        );

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $realMimeType
        ];
    }

    // Update the store method
    public function store(Request $request)
    {
        try {
            // Validate basic resource data
            $validated = $request->validate([
                'title' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-_.()]+$/',
                'description' => 'required|string|max:2000',
                'course_id' => 'required|exists:courses,id',
                'resource_category_id' => 'required|exists:resource_categories,id',
                'attachments.*' => 'file|max:10240', // 10MB max per file
            ]);

            \DB::beginTransaction();

            // Create resource
            $resource = Resource::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'course_id' => $validated['course_id'],
                'resource_category_id' => $validated['resource_category_id'],
                'user_id' => auth()->id(),
            ]);

            // Handle file attachments securely
            if ($request->hasFile('attachments')) {
                $attachments = [];
                
                foreach ($request->file('attachments') as $file) {
                    try {
                        $fileData = $this->validateAndStoreFile($file, $resource->id);
                        
                        $attachment = $resource->attachments()->create([
                            'file_path' => $fileData['path'],
                            'original_name' => $fileData['original_name'],
                            'file_size' => $fileData['size'],
                            'mime_type' => $fileData['mime_type'],
                        ]);
                        
                        $attachments[] = $attachment;
                        
                    } catch (\InvalidArgumentException $e) {
                        \DB::rollBack();
                        return response()->json([
                            'message' => 'File validation failed',
                            'errors' => ['attachments' => [$e->getMessage()]]
                        ], 422);
                    }
                }
            }

            \DB::commit();

            return response()->json([
                'message' => 'Resource created successfully',
                'data' => $resource->load('attachments', 'course', 'resourceCategory', 'user:id,username')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            Log::error('Resource creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Failed to create resource',
                'errors' => ['general' => ['An unexpected error occurred']]
            ], 500);
        }
    }

    // Add secure file download method
    public function downloadAttachment($resourceId, $attachmentId)
    {
        try {
            $resource = Resource::findOrFail($resourceId);
            $attachment = $resource->attachments()->findOrFail($attachmentId);
            
            // Check if user has access to this resource
            if (!$this->userCanAccessResource($resource)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            if (!Storage::disk('private')->exists($attachment->file_path)) {
                return response()->json(['message' => 'File not found'], 404);
            }
            
            return Storage::disk('private')->download(
                $attachment->file_path,
                $attachment->original_name
            );
            
        } catch (\Exception $e) {
            Log::error('File download failed', [
                'resource_id' => $resourceId,
                'attachment_id' => $attachmentId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => 'Download failed'], 500);
        }
    }

    private function userCanAccessResource($resource)
    {
        $user = auth()->user();
        
        // Resource owner can always access
        if ($resource->user_id === $user->id) {
            return true;
        }
        
        // Check if user is enrolled in the same course
        return $user->enrolledCourses()->where('course_id', $resource->course_id)->exists();
    }
}
