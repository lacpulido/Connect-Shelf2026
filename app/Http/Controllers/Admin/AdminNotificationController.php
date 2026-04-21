<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private function getAdmin(Request $request): ?User
    {
        return User::find(session('admin_user_id'));
    }

    public function index(Request $request)
    {
        $admin = $this->getAdmin($request);

        if (!$admin) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        $notifications = $admin->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'time' => $notification->created_at->diffForHumans(),
                    'read' => $notification->read_at !== null,
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $admin->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $admin = $this->getAdmin($request);

        if ($admin) {
            $admin->unreadNotifications->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $admin = $this->getAdmin($request);

        if ($admin) {
            $notification = $admin->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
            }
        }

        return response()->json(['success' => true]);
    }
}