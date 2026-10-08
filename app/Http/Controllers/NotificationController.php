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
        $query = $user ? $user->notifications()->where('channel', 'database') : null;
        $notifications = $query ? (clone $query)->latest()->take(20)->get() : [];

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $query ? (clone $query)->whereNull('read_at')->count() : 0,
        ]);
    }

    public function markAsRead(string|int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $notification = $user ? $user->notifications()->where('channel', 'database')->where('id', $id)->first() : null;
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
            $user->notifications()->where('channel', 'database')->whereNull('read_at')->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
