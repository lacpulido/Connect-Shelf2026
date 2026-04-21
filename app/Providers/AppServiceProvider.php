<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL; 
use Inertia\Inertia;
use App\Models\Notification;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ✅ EKSALTONG DAGDAG PARA SA PRODUCTION HTTPS
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Inertia::share([
            'auth' => function () {
                if (!Auth::check()) {
                    return null;
                }

                $user = Auth::user();

                $roleIds = DB::table('role_user')
                    ->where('user_id', $user->id)
                    ->pluck('role_id')
                    ->map(fn ($id) => (int) $id)
                    ->toArray();

                return [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name'  => $user->last_name,
                        'email'      => $user->email,
                        'user_type'  => (int) $user->user_type,
                        'role_ids'   => $roleIds,
                        'is_dept_chair'   => in_array(4, $roleIds),
                        'is_focal_person' => in_array(3, $roleIds),
                    ],
                ];
            },

            'notifications' => function () {
                if (!Auth::check()) {
                    return [];
                }

                return Notification::where('user_id', Auth::id())
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(function ($n) {
                        return [
                            'id'             => $n->id,
                            'title'          => $n->title,
                            'message'        => $n->message,
                            'status'         => $n->status,
                            'type'           => $n->type,
                            'reference_id'   => $n->reference_id,
                            'reference_type' => $n->reference_type,
                            'meta'           => $n->meta ? json_decode($n->meta, true) : null,
                            'created_at'     => $n->created_at->toDateTimeString(),
                        ];
                    })
                    ->values();
            },

            'unread_notifications_count' => function () {
                if (!Auth::check()) {
                    return 0;
                }

                return Notification::where('user_id', Auth::id())
                    ->where('status', 'UNREAD')
                    ->count();
            },
        ]);
    }
}
