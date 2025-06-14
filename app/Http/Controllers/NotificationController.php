<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class NotificationController extends Controller
{

    public function index()
    {
        $notifications = Auth::user()->notifications()->latest()->get();
        return response()->json($notifications);
    }


    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->update(['read' => true]);
        return response()->json(['success' => true]);
    }
}
