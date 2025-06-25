<?php

namespace App\Http\Controllers;

use App\Models\SavedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class SavedItemController extends Controller
{
        /**
     * Return resources, events and study groups saved by the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
    
        $perPage = $request->input('per_page', 10);
    
        // Step 1: Paginate saved items (with polymorphic relation)
        $savedItems = $user->savedItems()
            ->with('saveable') // polymorphic: loads Resource or Event
            ->orderBy('created_at', 'desc') // assuming this is "saved_at"
            ->paginate($perPage);
    
        // Step 2: Transform for frontend clarity
                $data = $savedItems->getCollection()->map(function ($item) {
            $saveable = $item->saveable;

            // If the linked model no longer exists (e.g., deleted), skip it
            if (!$saveable) {
                return null;
            }

            // Load extra relationships based on the model type
            if ($saveable instanceof \App\Models\Resource) {
                $saveable->load(['attachments', 'user', 'course']);
            } elseif ($saveable instanceof \App\Models\Event) {
                $saveable->load(['user', 'club']);
            } elseif ($saveable instanceof \App\Models\StudyGroup) {
                $saveable->load(['creator', 'course']);
            }

            return [
                'type'     => class_basename($item->saveable_type),
                'saved_at' => $item->created_at,
                'data'     => $saveable,
            ];
        })->filter()->values();
    
        // Step 3: Replace original collection with transformed one
        $paginated = new LengthAwarePaginator(
            $data,
            $savedItems->total(),
            $savedItems->perPage(),
            $savedItems->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    
        return response()->json($paginated);
    }
}
