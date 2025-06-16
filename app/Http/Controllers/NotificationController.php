<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class NotificationController extends Controller
{

    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->with('sender') // eager load sender
            ->latest()
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read' => $notification->read,
                    'created_at' => $notification->created_at,
                    'sender_id' => $notification->sender_id,
                    'sender_name' => $notification->sender ? $notification->sender->first_name . ' ' . $notification->sender->last_name : 'System',
                ];
            });

        return response()->json($notifications);
    }



    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->update(['read' => true]);
        return response()->json(['success' => true]);
    }
}
