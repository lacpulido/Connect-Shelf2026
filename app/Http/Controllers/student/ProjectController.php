<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreProjectRequest;
use App\Models\College;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function create()
    {
        if (! Auth::check() || (int) Auth::user()->user_type !== 2) {
            abort(403);
        }

        $user = Auth::user();
        $userId = $user->id;

        $hasAnyProject = Project::withTrashed()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->exists();

        if ($hasAnyProject) {
            return redirect()
                ->route('student.dashboard')
                ->with('error', 'You can only create one project.');
        }

        return Inertia::render('Student/Create', [
            'colleges' => College::select('id', 'name')->get(),
            'departments' => Department::select('id', 'name', 'college_id')->get(),
            'authUser' => [
                'college_id' => $user->college_id,
                'department_id' => $user->department_id,
            ],
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        DB::beginTransaction();

        try {
            $creatorId = Auth::id();
            $user = Auth::user();
            $validated = $request->validated();

            $hasAnyProject = Project::withTrashed()
                ->where(function ($query) use ($creatorId) {
                    $query->where('user_id', $creatorId)
                        ->orWhereHas('researchers', function ($q) use ($creatorId) {
                            $q->where('users.id', $creatorId);
                        });
                })
                ->exists();

            if ($hasAnyProject) {
                DB::rollBack();

                return back()->withErrors([
                    'title' => 'You can only create one project.',
                ])->withInput();
            }

            /**
             * Locked values from authenticated user/server.
             * Academic year is intentionally taken from the validated user input.
             */
            $collegeId = $user->college_id;
            $departmentId = $user->department_id;
            $academicYear = $validated['academic_year'];
            $semester = '1st Semester';
            $projectType = $this->getProjectType($departmentId);

            $project = Project::create([
                'title' => $validated['title'],
                'project_type' => $projectType,
                'college_id' => $collegeId,
                'department_id' => $departmentId,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'user_id' => $creatorId,
                'status' => 'Ongoing',
                'adviser_id' => $validated['adviser_id'],
            ]);

            $researchers = $validated['researchers'] ?? [];

            if (! in_array($creatorId, $researchers, true)) {
                $researchers[] = $creatorId;
            }

            $researchers = array_values(array_unique(array_map('intval', $researchers)));

            $project->researchers()->sync($researchers);

            foreach ($researchers as $researcherId) {
                if ((int) $researcherId === (int) $creatorId) {
                    continue;
                }

                Notification::create([
                    'user_id' => $researcherId,
                    'title' => 'Added as Researcher',
                    'message' => $user->first_name . ' ' . $user->last_name .
                        ' added you to the project "' . $project->title . '".',
                    'type' => 'researcher_added',
                    'reference_id' => $project->id,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);
            }

            Notification::create([
                'user_id' => (int) $validated['adviser_id'],
                'title' => 'Added as Adviser',
                'message' => $user->first_name . ' ' . $user->last_name .
                    ' added you as adviser for "' . $project->title . '".',
                'type' => 'adviser_added',
                'reference_id' => $project->id,
                'reference_type' => 'project',
                'status' => 'UNREAD',
            ]);

            $focalPersonIds = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Focal Person');
                })
                ->pluck('id')
                ->unique()
                ->values();

            foreach ($focalPersonIds as $focalPersonId) {
                Notification::create([
                    'user_id' => (int) $focalPersonId,
                    'title' => 'New Project Created',
                    'message' => $user->first_name . ' ' . $user->last_name .
                        ' created a new project titled "' . $project->title . '".',
                    'type' => 'project_created',
                    'reference_id' => $project->id,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('student.dashboard')
                ->with('success', 'Project created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    private function getProjectType($departmentId): ?string
    {
        $department = Department::find($departmentId);

        if (! $department) {
            return null;
        }

        $name = strtolower(trim($department->name));

        if ($name === 'information technology') {
            return 'Capstone';
        }

        if ($name === 'computer science') {
            return 'Thesis';
        }

        return null;
    }
}