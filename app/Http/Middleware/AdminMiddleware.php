<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminId = $request->session()->get('admin_user_id');

        if (!$adminId) {
            return redirect()->route('admin.login');
        }

        $admin = User::query()
            ->where('id', $adminId)
            ->where('user_type', 1)
            ->where('is_active', true)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->first();

        if (!$admin) {
            $request->session()->forget('admin_user_id');

            return redirect()->route('admin.login')
                ->withErrors([
                    'email' => 'Your admin session is invalid.',
                ]);
        }

        return $next($request);
    }
}