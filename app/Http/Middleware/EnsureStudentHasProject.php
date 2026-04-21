<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Project;
use App\Models\User;

class EnsureStudentHasProject
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only apply to students
        if ($user->user_type === 2) {

            $hasProject = $user->projects()->exists(); // adjust relationship name if needed

            // If no project, force create page
            if (! $hasProject && ! $request->routeIs('projects.create', 'projects.store')) {
                return redirect()->route('projects.create');
            }
        }

        return $next($request);
    }
}
