<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function userSummary(Request $request): Response
    {
        return Inertia::render('Admin/UserSummary', [
            'message' => 'User Summary page placeholder.',
        ]);
    }

    public function projectSummary(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $departmentId = $request->input('department');

        $departments = Department::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $projects = Project::query()
            ->with([
                'user:id,first_name,last_name,department_id',
                'user.department:id,name',
                'adviser:id,first_name,last_name',
                'schedule',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('adviser', function ($adviserQuery) use ($search) {
                            $adviserQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->whereHas('user', function ($userQuery) use ($departmentId) {
                    $userQuery->where('department_id', $departmentId);
                });
            })
            ->latest('id')
            ->get()
            ->map(function ($project) {
                $ownerName = trim(collect([
                    $project->user?->first_name,
                    $project->user?->last_name,
                ])->filter()->implode(' '));

                $adviserName = trim(collect([
                    $project->adviser?->first_name,
                    $project->adviser?->last_name,
                ])->filter()->implode(' '));

                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'description' => $project->description,
                    'department' => $project->user?->department?->name ?? 'No Department',
                    'department_id' => $project->user?->department?->id,
                    'owner' => $ownerName !== '' ? $ownerName : 'N/A',
                    'adviser' => $adviserName !== '' ? $adviserName : 'N/A',
                    'schedule' => $project->schedule ? [
                        'preferred_date' => $project->schedule->preferred_date ?? null,
                        'preferred_time' => $project->schedule->preferred_time ?? null,
                        'venue' => $project->schedule->venue ?? null,
                    ] : null,
                ];
            })
            ->values();

        $departmentSummaries = $departments
            ->map(function ($department) use ($projects) {
                $departmentProjects = $projects
                    ->where('department_id', $department->id)
                    ->values();

                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'total_projects' => $departmentProjects->count(),
                    'projects' => $departmentProjects->take(5)->values(),
                ];
            })
            ->filter(fn ($department) => $department['total_projects'] > 0)
            ->values();

        return Inertia::render('Admin/ProjectSummary', [
            'filters' => [
                'search' => $search,
                'department' => $departmentId,
            ],
            'departments' => $departments,
            'departmentSummaries' => $departmentSummaries,
            'projects' => $projects,
            'stats' => [
                'total_projects' => $projects->count(),
                'total_departments_with_projects' => $departmentSummaries->count(),
            ],
        ]);
    }
}