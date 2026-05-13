<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssignAdviserToProjectsController extends Controller
{
    public function index(): Response
    {
        $authUser = Auth::user();
        $departmentId = $authUser->department_id;
        $allowedProjectType = $this->getAllowedProjectTypeByDepartment($authUser?->department?->name);

        $projects = Project::with([
                'user.department',
                'adviser.department',
                'researchers',
                'panelists',
                'schedule',
            ])
            ->whereNotNull('adviser_id')
            ->whereRaw('LOWER(status) = ?', ['ongoing'])
            ->whereHas('user', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->when($allowedProjectType, function ($query) use ($allowedProjectType) {
                $query->where('project_type', $allowedProjectType);
            })
            ->orderByDesc('created_at')
            ->get();

        $faculties = User::with(['roles', 'department'])
            ->where('user_type', 1)
            ->where('department_id', $departmentId)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Administrator'))
            ->orderBy('first_name')
            ->get();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('focalperson/AssignAdviserToProjects', [
            'projects'    => $projects,
            'faculties'   => $faculties,
            'departments' => $departments,
            'filters'     => [],
        ]);
    }

    public function assign(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'faculty_id' => ['required', 'exists:users,id'],
        ]);

        $this->authorizeAccess($project);

        $faculty = User::with(['roles', 'department'])->findOrFail($request->faculty_id);

        if ((int) $faculty->department_id !== (int) Auth::user()->department_id) {
            return back()->withErrors([
                'faculty' => 'You can only assign faculty members from your department.',
            ]);
        }

        if ($faculty->roles->contains('name', 'Administrator')) {
            return back()->withErrors([
                'faculty' => 'Administrator cannot be assigned as adviser.',
            ]);
        }

        $adviserRole = Role::where('name', 'Adviser')->firstOrFail();

        DB::transaction(function () use ($project, $faculty, $adviserRole) {
            $project->update([
                'adviser_id' => $faculty->id,
            ]);

            $faculty->roles()->syncWithoutDetaching([$adviserRole->id]);
        });

        $project->load(['researchers', 'user']);

        $this->notifyAllParties($project, $faculty);

        return back()->with('success', 'Adviser assigned and all users notified.');
    }

    private function authorizeAccess(Project $project): void
    {
        $authUser = Auth::user();
        $projectDeptId = $project->user?->department_id;
        $allowedProjectType = $this->getAllowedProjectTypeByDepartment($authUser?->department?->name);

        abort_if(
            (int) $projectDeptId !== (int) $authUser->department_id,
            403,
            'Unauthorized action.'
        );

        if ($allowedProjectType && strcasecmp((string) $project->project_type, $allowedProjectType) !== 0) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function getAllowedProjectTypeByDepartment(?string $departmentName): ?string
    {
        $normalized = strtolower(trim((string) $departmentName));

        return match ($normalized) {
            'information technology', 'it' => 'Capstone',
            'computer science', 'cs' => 'Thesis',
            default => null,
        };
    }

    private function notifyAllParties(Project $project, User $faculty): void
    {
        $facultyName = trim(($faculty->first_name ?? '') . ' ' . ($faculty->last_name ?? ''));

        $studentMessage = 'Your project "' . $project->title . '" has been reviewed. ' .
            'Adviser ' . $facultyName . ' has been assigned.';

        $studentIds = collect([$project->user_id])
            ->merge($project->researchers->pluck('id'))
            ->filter()
            ->unique();

        foreach ($studentIds as $studentId) {
            NotificationService::send(
                $studentId,
                'Adviser Assigned',
                $studentMessage,
                'adviser_assigned',
                $project->id,
                'project'
            );
        }

        NotificationService::send(
            $faculty->id,
            'New Adviser Assignment',
            'You have been assigned as adviser to project "' . $project->title . '".',
            'adviser_role_assigned',
            $project->id,
            'project'
        );
    }
}