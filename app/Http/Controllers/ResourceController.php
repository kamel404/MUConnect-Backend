<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Models\SavedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceController extends Controller
{
    // ... other methods ...

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
}
