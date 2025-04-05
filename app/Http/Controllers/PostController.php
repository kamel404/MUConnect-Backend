<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        // Fetch all posts
        $posts = Post::with('user')->latest()->paginate(10);
        return response()->json($posts);
    }

    public function show($id)
    {
        // Fetch a single post by ID
        $post = Post::with('user')->findOrFail($id);
        return response()->json($post);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'content' => 'nullable|string',
            'type' => 'nullable|in:Default,Study Group,Course Material,Event,Poll',
            'is_pinned' => 'boolean',
        ]);
    
        $post = Post::create([
            'user_id' => auth()->id(),
            'content' => $validatedData['content'] ?? null,
            'type' => $validatedData['type'] ?? 'Default',
            'is_pinned' => $validatedData['is_pinned'] ?? false,
        ]);
    
        return response()->json(['message' => 'Post created successfully', 'post' => $post], 201);
    }
    

    public function update(Request $request, $id)
    {
        // Validate and update an existing post
        $validatedData = $request->validate([
            'content' => 'nullable|string',
            'type' => 'nullable|in:Default,Study Group,Course Material,Event,Poll',
            'is_pinned' => 'boolean',
        ]);

        $post = Post::findOrFail($id);
        $post->update($validatedData);
        return response()->json(['message' => 'Post updated successfully', 'post' => $post]);
    }

    public function destroy($id)
    {
        // Soft delete a post
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }
}
