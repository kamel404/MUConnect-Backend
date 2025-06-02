<?php

namespace App\Http\Controllers;
use App\Http\Requests\StorePostRequest;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PostController extends Controller
{

    public function index()
    {
        $posts = Post::with('attachment')->latest()->get();

        return response()->json($posts);
    }

    // GET /api/posts/{id}
    public function show($id)
    {
        $post = Post::with('attachment')->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json($post);
    }

    public function store(StorePostRequest $request)
    {
        //

        DB::beginTransaction();

        try {
            $attachment = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $checksum = hash_file('sha256', $file->getRealPath());

                $attachment = Attachment::where('checksum', $checksum)->first();
                if (!$attachment) {

                    $mimeType = $file->getMimeType();

                    $type = explode('/', $mimeType)[0];

                    $categoryFolder = match ($type) {
                        'image' => 'images',
                        'video' => 'videos',
                        'document' => 'documents',
                        default => 'others',
                    };

                    $storagePath = "attachments/{$categoryFolder}";
                    $path = $file->store($storagePath, 'public');

                    $attachment = Attachment::create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'checksum' => $checksum,
                    ]);
                }
            }

            $post = Post::create([
                //todo highly imprtant to use auth()->id() instead of hardcoding user_id I just placed it here for testing
                //'user_id' => auth()->id(), // inside create() during store()
                'user_id' => 1,
                'title' => $request->input('title'),
                'content' => $request->input('content'),

            ]);

            if ($attachment) {
                $post->attachment()->attach($attachment);
            }

            DB::commit();

            return response()->json([
                'message' => 'Post created successfully',
                'post' => $post->load('attachment'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error creating post',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
    //Delete a post and its attachments
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        // //auth that this users own the post 
        // if (!$post) {
        //     return response()->json(['message' => 'Post not found'], 404);
        // }
        // if ($post->user_id !== auth()->id()) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }
        //here it is giving a warning that the $post maybe collection but it is impossible. false alarm 
        return $this->deletePostWithCleanup($post);
    }
    //todo this can be moved to a service class for better separation of concerns but for now I will keep it here
    // private function deletePostWithCleanup(Post $post)
    // {
    //     $post->load('attachment');
    //     $attachment = $post->attachment;

    //     foreach ($post->attachments as $attachment) {
    //         $post->attachments()->detach($attachment->id);

    //         if ($attachment->posts()->count() === 0) {
    //             if (Storage::exists($attachment->path)) {
    //                 Storage::delete($attachment->path);
    //             }
    //             $attachment->delete();
    //         }
    //     }

    //     $post->delete();
    // }
    private function deletePostWithCleanup(Post $post)
    {
        $post->load('attachment');

        $attachment = $post->attachment;

        $post->delete(); // delete the post first
        if ($attachment && $attachment->posts()->count() === 0) {
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }

            $attachment->delete(); // delete the orphaned attachment
        }

        return response()->json(['message' => 'Post and orphaned attachment deleted successfully.']);
    }

}


//I keep old functions here for reference, but they are not used in the current implementation.
// public function store(StorePostRequest $request)
// {

//     $post = Post::create([
//         'user_id' => auth()->id(),
//         'title' => $request->input('title'),
//         'content' => $request->input('content')
//     ]);

//     if ($request->hasFile('attachments')) {
//         foreach ($request->file('attachments') as $file) {
//             $path = $file->store('attachments', 'public');
//             Attachment::create([
//                 'post_id' => $post->id,
//                 'file_path' => $path,
//                 'mime_type' => $file->getClientMimeType()
//             ]);
//         }
//     }

//     return response()->json(['message' => 'Post created successfully!', 'post' => $post], 201);
// }