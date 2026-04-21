<?php

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $webUser = $request->user();

        $adminId = $request->session()->get('admin_user_id');

        $adminUser = null;

        if ($adminId) {
            $adminUser = User::query()
                ->with('roles')
                ->where('id', $adminId)
                ->where('user_type', 1)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Administrator');
                })
                ->first();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $webUser ? [
                    'id' => $webUser->id,
                    'first_name' => $webUser->first_name,
                    'middle_name' => $webUser->middle_name,
                    'last_name' => $webUser->last_name,
                    'extension_name' => $webUser->extension_name,
                    'full_name' => $webUser->full_name,
                    'email' => $webUser->email,
                    'user_type' => (int) $webUser->user_type,

                    'is_dept_chair' => $webUser->roles()
                        ->where('name', 'Department ChairPerson')
                        ->exists(),

                    'is_focal_person' => $webUser->roles()
                        ->where('name', 'Focal Person')
                        ->exists(),

                    'roles' => $webUser->roles()
                        ->pluck('name')
                        ->values(),
                ] : null,

                'admin' => $adminUser ? [
                    'id' => $adminUser->id,
                    'first_name' => $adminUser->first_name,
                    'middle_name' => $adminUser->middle_name,
                    'last_name' => $adminUser->last_name,
                    'extension_name' => $adminUser->extension_name,
                    'full_name' => $adminUser->full_name,
                    'email' => $adminUser->email,
                    'user_type' => (int) $adminUser->user_type,
                    'roles' => $adminUser->roles->pluck('name')->values(),
                ] : null,
            ],

            'notifications' => fn () =>
                $webUser
                    ? Notification::where('user_id', $webUser->id)
                        ->latest()
                        ->limit(20)
                        ->get()
                        ->map(fn ($n) => [
                            'id' => $n->id,
                            'title' => $n->title,
                            'message' => $n->message,
                            'status' => $n->status,
                            'type' => $n->type,
                            'reference_id' => $n->reference_id,
                            'reference_type' => $n->reference_type,
                            'created_at' => $n->created_at
                                ? $n->created_at->copy()->timezone(config('app.timezone'))->toDateTimeString()
                                : null,
                        ])
                        ->values()
                    : [],

            'unread_notifications_count' => fn () =>
                $webUser
                    ? Notification::where('user_id', $webUser->id)
                        ->where('status', 'UNREAD')
                        ->count()
                    : 0,

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}