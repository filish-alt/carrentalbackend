<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // Get notifications by user ID
    public function getByUserId($userId)
    {
        $notifications = $this->notificationService->getNotificationsByUserId($userId);

        return response()->json($notifications);
    }

    // Create new notification
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $notification = $this->notificationService->createNotification($data);

        return response()->json($notification, 201);
    }

    // Mark a single notification as read
    public function markAsRead($id)
    {
        $notification = $this->notificationService->markAsRead($id);

        return response()->json($notification);
    }

    // Delete a notification
    public function destroy($id)
    {
        $result = $this->notificationService->deleteNotification($id);

        return response()->json($result);
    }

    // Update preferences for authenticated user
    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'preferences' => 'required|array',
            'preferences.email' => 'boolean',
            'preferences.sms' => 'boolean',
            'preferences.push' => 'boolean',
        ]);

        $user = Auth::user();
        $result = $this->notificationService->updatePreferences($data['preferences'], $user);

        return response()->json($result);
    }
}
