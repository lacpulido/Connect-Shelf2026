<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnUserType
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }
        // Faculty
        if ($user->user_type === 1) {
            return redirect()->route('faculty.dashboard');
        }

        // Student
        if ($user->user_type === 2) {
            return redirect()->route('student.dashboard');
        }

        abort(403, 'Invalid user type.');
    }
}
