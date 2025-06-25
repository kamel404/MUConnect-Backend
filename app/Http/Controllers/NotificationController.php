<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class NotificationController extends Controller
{

    public function index()
{
    $notifications = Auth::user()->notifications()
        ->with('sender')
        ->latest()
        ->paginate(5);

    $notifications->getCollection()->transform(function ($notification) {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read' => $notification->read,
            'created_at' => $notification->created_at,
            'sender_id' => $notification->sender_id,
            'sender_name' => $notification->sender
                ? $notification->sender->first_name . ' ' . $notification->sender->last_name
                : 'System',
            'sender_avatar' => $notification->sender
                ? $notification->sender->avatar_url
                : asset('storage/avatars/default.png'),
            'url' => $notification->data['url'] ?? null,
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

    /**
     * Delete a notification for the authenticated user
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * Mark all notifications as read for authenticated user
     */
    public function markAllAsRead()
    {
        Auth::user()->notifications()->update(['read' => true]);
        return response()->json(['message' => 'All notifications marked as read']);
    }
}
