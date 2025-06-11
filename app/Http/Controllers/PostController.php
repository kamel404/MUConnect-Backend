<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('attachments')->latest()->get();
        return response()->json($posts);
    }

    public function show($id)
    {
        $post = Post::with('attachments')->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post);
    }


    public function store(StorePostRequest $request)
    {
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
                    $type = explode('/', $mimeType)[0];

                    $categoryFolder = match ($type) {
                        'image' => 'images',
                        'video' => 'videos',
                        'application' => 'documents',
                        default => 'others',
                    };

                    $storagePath = "attachments/{$categoryFolder}";
                    $path = $file->store($storagePath, 'public');

                    $newAttachment = Attachment::create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $mimeType,
                        'checksum' => $checksum,
                    ]);

                    $attachments[] = $newAttachment;
                }
            }

            $post = Post::create([
                'user_id' => auth()->id() ?? 1,
                'title' => $request->input('title'),
                'content' => $request->input('content'),
            ]);

            if (!empty($attachments)) {
                $post->attachments()->attach(collect($attachments)->pluck('id')->toArray());
            }

            DB::commit();

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post->load('attachments'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $post = Post::with('attachments')->findOrFail($id);

            $attachments = $post->attachments;

            // Detach all attachments (pivot cleanup)
            $post->attachments()->detach();

            // Delete the post
            $post->delete();

            // Delete orphaned attachments and files
            foreach ($attachments as $attachment) {
                if ($attachment->posts()->count() === 0) {
                    if (Storage::disk('public')->exists($attachment->file_path)) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                    $attachment->delete();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Post and orphaned attachments deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Error deleting post', 'error' => $e->getMessage()], 500);
        }
    }

    // Old / unused methods commented out for reference...
}
