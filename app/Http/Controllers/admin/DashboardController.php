<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        if (!$request->session()->has('admin_user_id')) {
            return redirect()->route('admin.login');
        }

        $stats = Project::selectRaw("
            COUNT(*) as total,
            SUM(status = 'Ongoing') as ongoing,
            SUM(status = 'Completed') as completed
        ")->first();

        $activities = $this->getRecentActivities();

        return Inertia::render('Admin/Dashboard', [
            'totalUsers' => User::count(),
            'totalProjects' => $stats->total ?? 0,
            'ongoingProjects' => $stats->ongoing ?? 0,
            'completedProjects' => $stats->completed ?? 0,
            'activities' => $activities,
        ]);
    }

    private function getRecentActivities(): array
    {
        $userActivities = User::query()
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => (int) ('1' . $user->id),
                    'name' => $user->name,
                    'title' => 'New user registered',
                    'description' => 'has registered a new account.',
                    'type' => 'user',
                    'time' => $user->created_at ? $user->created_at->format('h:i A') : '',
                    'date' => $user->created_at ? $user->created_at->format('M d, Y') : '',
                    'sort_datetime' => $user->created_at,
                ];
            });

        $newProjectActivities = Project::query()
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => (int) ('2' . $project->id),
                    'name' => '',
                    'title' => 'New project created',
                    'description' => 'Project "' . $project->title . '" was created.',
                    'type' => 'project',
                    'time' => $project->created_at ? $project->created_at->format('h:i A') : '',
                    'date' => $project->created_at ? $project->created_at->format('M d, Y') : '',
                    'sort_datetime' => $project->created_at,
                ];
            });

        $completedProjectActivities = Project::query()
            ->where('status', 'Completed')
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => (int) ('3' . $project->id),
                    'name' => '',
                    'title' => 'Project completed',
                    'description' => 'Project "' . $project->title . '" has been completed.',
                    'type' => 'assignment',
                    'time' => $project->updated_at ? $project->updated_at->format('h:i A') : '',
                    'date' => $project->updated_at ? $project->updated_at->format('M d, Y') : '',
                    'sort_datetime' => $project->updated_at,
                ];
            });

        return collect()
            ->merge($userActivities)
            ->merge($newProjectActivities)
            ->merge($completedProjectActivities)
            ->sortByDesc('sort_datetime')
            ->take(10)
            ->map(function ($activity) {
                unset($activity['sort_datetime']);
                return $activity;
            })
            ->values()
            ->toArray();
    }
}