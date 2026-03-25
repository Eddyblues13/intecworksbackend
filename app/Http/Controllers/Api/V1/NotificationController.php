<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get a paginated list of notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Paginate unread and read notifications
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'unread_count'  => $user->unreadNotifications()->count(),
            'notifications' => $notifications->items(),
            'current_page'  => $notifications->currentPage(),
            'last_page'     => $notifications->lastPage(),
        ]);
    }

    /**
     * Mark a specific notification, or all, as read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $notificationId = $request->input('notification_id');

        if ($notificationId) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            // Mark all as read
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'message'      => 'Notifications marked as read.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Update the FCM device token for the authenticated user.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $data['fcm_token'],
        ]);

        return response()->json(['message' => 'FCM token updated successfully.']);
    }
}
