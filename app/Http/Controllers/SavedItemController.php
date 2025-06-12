<?php

namespace App\Http\Controllers;

use App\Models\SavedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedItemController extends Controller
{
    // List all saved items for the authenticated user
    public function index(Request $request)
    {
        $user = Auth::user();
        $savedItems = $user->savedItems()->with('saveable')->get();
        return response()->json($savedItems);
    }
}
