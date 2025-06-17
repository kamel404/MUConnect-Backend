<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\Attachment;
use App\Models\SavedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreResourceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateResourceRequest;
class ResourceController extends Controller
{

    public function index()
    {
        $resource = Resource::with('attachments')->latest()->get();
        return response()->json($resource);
    }


    public function show($id)
    {
        $resource = Resource::with('attachments')->find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json($resource);
    }
    // Save a resource for the authenticated user
    public function save($id)
    {
        $user = Auth::user();
        $resource = Resource::findOrFail($id);

        $saved = SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'saveable_id' => $resource->id,
            'saveable_type' => Resource::class,
        ]);

        return response()->json(['saved' => true, 'item' => $saved], 201);
    }

    // Unsave a resource for the authenticated user
    public function unsave($id)
    {
        $user = Auth::user();
        $resource = Resource::findOrFail($id);

        $deleted = SavedItem::where([
            'user_id' => $user->id,
            'saveable_id' => $resource->id,
            'saveable_type' => Resource::class,
        ])->delete();

        return response()->json(['deleted' => $deleted > 0]);
    }

    // Create a new resource with optional attachments
    public function store(Request $request)
    {
        $user = Auth::user();
        $maxAttachments = 10; // Set your limit here

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'attachments' => 'sometimes',
            'attachments.*' => 'file|max:10240',
        ]);

        // Normalize attachments to always be an array
        $files = $request->file('attachments');
        if (!$files) {
            $files = [];
        } elseif ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }
        $newCount = count($files);
        if ($newCount > $maxAttachments) {
            return response()->json([
                'error' => "You can only attach up to $maxAttachments files per resource."
            ], 422);
        }

        $resource = Resource::create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($files) {
            foreach ($files as $uploadedFile) {
                $path = $uploadedFile->store('attachments', 'public');

                $resource->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $this->guessFileType($uploadedFile),
                    'mime_type' => $uploadedFile->getClientMimeType(),
                    'checksum' => md5_file($uploadedFile->getRealPath()),
                ]);
            }
        }

        return response()->json(['resource' => $resource->load('attachments')], 201);
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
            ]);

            if (!empty($attachments)) {
                $resource->attachments()->attach(collect($attachments)->pluck('id')->toArray());
            }

            DB::commit();

            return response()->json([
                'message' => 'Resource created successfully',
                'resource' => $resource->load('attachments'),
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
            // Find the resource and check ownership
            $resource = Resource::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

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

            DB::commit();
            return response()->json([
                'message' => 'Resource updated successfully',
                'resource' => $resource->load('attachments'),
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
    // This update method is not working
    // public function update(Request $request, $id)
    // {
    //     $user = Auth::user();
    //     $maxAttachments = 10;

    //     $resource = Resource::where('user_id', $user->id)->findOrFail($id);

    //     $data = $request->validate([
    //         'title' => 'nullable|string',
    //         'description' => 'nullable|string',
    //         'attachments' => 'sometimes',
    //         'attachments.*' => 'file|max:10240',
    //     ]);

    //     // Only update fields that are present in the request
    //     $updateData = [];
    //     if ($request->has('title')) {
    //         $updateData['title'] = $request->input('title');
    //     }
    //     if ($request->has('description')) {
    //         $updateData['description'] = $request->input('description');
    //     }
    //     if (!empty($updateData)) {
    //         $resource->update($updateData);
    //     }

    //     // Normalize files to array (robust, Postman compatible)
    //     $files = [];
    //     if ($request->hasFile('attachments')) {
    //         $rawFiles = $request->file('attachments');
    //         if (is_array($rawFiles)) {
    //             $files = $rawFiles;
    //         } elseif ($rawFiles instanceof \Illuminate\Http\UploadedFile) {
    //             $files = [$rawFiles];
    //         }
    //     }

    //     if (count($files)) {
    //         if (count($files) > $maxAttachments) {
    //             return response()->json([
    //                 'error' => "You can only attach up to $maxAttachments files per resource."
    //             ], 422);
    //         }
    //         // Delete old attachments (DB + storage)
    //         foreach ($resource->attachments as $attachment) {
    //             \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
    //             $attachment->delete();
    //         }
    //         // Add new attachments
    //         foreach ($files as $uploadedFile) {
    //             $path = $uploadedFile->store('attachments', 'public');
    //             $resource->attachments()->create([
    //                 'file_path' => $path,
    //                 'file_type' => $this->guessFileType($uploadedFile),
    //                 'mime_type' => $uploadedFile->getClientMimeType(),
    //                 'checksum' => md5_file($uploadedFile->getRealPath()),
    //             ]);
    //         }
    //         $resource->touch();
    //     }

    //     return response()->json(['resource' => $resource->load('attachments')]);
    // }


    // Delete a resource (and cascade attachments via DB)
    public function destroy($id)
    {
        $user = Auth::user();
        $resource = Resource::where('user_id', $user->id)->findOrFail($id);

        // Delete attached files from storage
        foreach ($resource->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $resource->delete();

        return response()->json(['deleted' => true]);
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

    public function destroyTest($id)
    {//todo write a clean code that would return the error message if the user is not authorized to delete this 
        $user = Auth::user();
        // Ensure the user is authorized to delete this resource
        $resource = Resource::with('attachments')->find($id);

        if (!$resource) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        if (!$resource->user_id !== $user->id) {
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
