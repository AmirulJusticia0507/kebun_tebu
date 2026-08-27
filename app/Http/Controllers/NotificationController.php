<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $notifications = $user ? $user->notifications()->take(20)->get() : [];
        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }

    public function markAsRead(string|int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $notification = $user ? $user->notifications()->where('id', $id)->first() : null;
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    }
}
