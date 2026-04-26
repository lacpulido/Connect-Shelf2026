<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccess
{
    public function handle(Request $request, Closure $next, ...$allowed): Response
    {
        // ❗ Check if logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 🔥 FORCE LOGOUT kapag inactive
        if ((int) $user->is_active === 0 || $user->status === 'Inactive') {
            Auth::logout();

            // Important: clear session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        // ❗ Role checking (existing logic)
        foreach ($allowed as $rule) {
            if ($this->hasAccess($user, $rule)) {
                return $next($request);
            }
        }

        return $this->redirectUnauthorizedUser($user);
    }

    private function hasAccess($user, string $rule): bool
    {
        return match ($rule) {
            'student' => (int) $user->user_type === 2,
            'faculty' => (int) $user->user_type === 1,
            default => method_exists($user, 'roles')
                && $user->roles()->where('name', $rule)->exists(),
        };
    }

    private function redirectUnauthorizedUser($user): Response
    {
        return match ((int) $user->user_type) {
            2 => redirect()->route('student.dashboard'),
            1 => redirect()->route('faculty.dashboard'),
            default => abort(403),
        };
    }
}