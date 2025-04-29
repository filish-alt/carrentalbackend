<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Get notifications by user ID
    public function getByUserId($userId)
    {
        $notifications = Notification::where('user_id', $userId)
                                     ->latest()
                                     ->get();
        return response()->json($notifications);
    }

    // Optional: Create new notification
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json($notification, 201);
    }

    // Optional: Mark as read
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json($notification);
    }

    // Optional: Delete notification
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    // Update notification preferences for authenticated user
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.email' => 'boolean',
            'preferences.sms' => 'boolean',
            'preferences.push' => 'boolean',
        ]);

        $user = Auth::user();
        $user->notification_preferences = $request->preferences;
        $user->save();

        return response()->json([
            'message' => 'Notification preferences updated successfully.',
            'notification_preferences' => $user->notification_preferences,
        ]);
    }
}
