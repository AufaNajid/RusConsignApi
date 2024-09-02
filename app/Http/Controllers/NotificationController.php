<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUnreadNotifications(Request $request)
    {
        $user = Auth::user();

        $unreadNotifications = $user->unreadNotifications;

        return response()->json($unreadNotifications);
    }

    public function getAllNotifications(Request $request)
    {
        $user = Auth::user();

        $allNotifications = $user->notifications;

        return response()->json($allNotifications);
    }
    public function markAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read']);
        } else {
            return response()->json(['message' => 'Notification not found'], 404);
        }
    }
}
