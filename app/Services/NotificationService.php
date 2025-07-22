<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Users;

class NotificationService
{
    public function getNotificationsByUserId($userId)
    {
        return Notification::where('user_id', $userId)
                          ->latest()
                          ->get();
    }

    public function createNotification(array $data)
    {
        $notification = Notification::create($data);
        return $notification;
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return $notification;
    }

    public function deleteNotification($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return ['message' => 'Notification deleted successfully'];
    }

    public function updatePreferences(array $preferences, $user)
    {
        $user->notification_preferences = $preferences;
        $user->save();

        return [
            'message' => 'Notification preferences updated successfully.',
            'notification_preferences' => $user->notification_preferences,
        ];
    }

    public function sendNotificationToUser($userId, $title, $message, $type = 'general')
    {
        return $this->createNotification([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    public function sendBulkNotification($userIds, $title, $message, $type = 'general')
    {
        $notifications = [];

        foreach ($userIds as $userId) {
            $notifications[] = [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($notifications);

        return ['message' => 'Bulk notifications sent successfully', 'count' => count($notifications)];
    }

    public function getUnreadCount($userId)
    {
        return Notification::where('user_id', $userId)
                          ->where('is_read', false)
                          ->count();
    }

    public function markAllAsRead($userId)
    {
        $count = Notification::where('user_id', $userId)
                           ->where('is_read', false)
                           ->update([
                               'is_read' => true,
                               'read_at' => now(),
                           ]);

        return ['message' => 'All notifications marked as read', 'count' => $count];
    }
}
