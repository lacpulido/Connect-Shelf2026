<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreProjectRequest;
use App\Models\College;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectPreferredAdviser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectController extends Controller
{
    private const STATUS_PROPOSAL = 'Proposal';

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

        $departments = Department::query()
            ->select('id', 'name')
            ->get()
            ->keyBy('id');

        $faculty = User::query()
            ->select([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'extension_name',
                'email',
                'user_type',
                'department_id',
                'adviser_is_visible',
            ])
            ->where('user_type', 1)
            ->where('adviser_is_visible', true)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (User $faculty) use ($departments) {
                $name = collect([
                    $faculty->first_name,
                    $faculty->middle_name,
                    $faculty->last_name,
                    $faculty->extension_name,
                ])->filter()->implode(' ');

                return [
                    'id' => $faculty->id,
                    'name' => $name,
                    'email' => $faculty->email,
                    'department_id' => $faculty->department_id,
                    'department_name' => $departments[$faculty->department_id]->name ?? 'No Department',
                    'adviser_is_visible' => (bool) $faculty->adviser_is_visible,
                ];
            })
            ->values();

        return Inertia::render('Student/Create', [
            'colleges' => College::select('id', 'name')->get(),
            'departments' => Department::select('id', 'name', 'college_id')->get(),
            'faculty' => $faculty,
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
                    'proposal_titles.0' => 'You can only create one project.',
                ])->withInput();
            }

            $preferredAdviserIds = array_values(array_unique(array_map(
                'intval',
                $validated['preferred_adviser_ids']
            )));

            if (count($preferredAdviserIds) !== 3) {
                DB::rollBack();

                return back()->withErrors([
                    'preferred_adviser_ids' => 'Please select exactly 3 preferred advisers.',
                ])->withInput();
            }

            $validAdviserCount = User::query()
                ->whereIn('id', $preferredAdviserIds)
                ->where('user_type', 1)
                ->where('adviser_is_visible', true)
                ->whereDoesntHave('roles', function ($query) {
                    $query->where('name', 'Administrator');
                })
                ->count();

            if ($validAdviserCount !== 3) {
                DB::rollBack();

                return back()->withErrors([
                    'preferred_adviser_ids' => 'One or more selected advisers are no longer available.',
                ])->withInput();
            }

            $proposalFiles = [];
            $proposalOriginalNames = [];

            if ($request->hasFile('proposal_files')) {
                foreach ($request->file('proposal_files') as $index => $file) {
                    $proposalFiles[$index] = $file->store('topic_proposals', 'public');
                    $proposalOriginalNames[$index] = $file->getClientOriginalName();
                }
            }

            $project = Project::create([
                'title' => $validated['proposal_titles'][0],
                'proposal_titles' => $validated['proposal_titles'],
                'proposal_files' => $proposalFiles,
                'proposal_original_names' => $proposalOriginalNames,
                'approved_proposal_index' => null,
                'project_type' => $this->getProjectType($user->department_id),
                'college_id' => $user->college_id,
                'department_id' => $user->department_id,
                'academic_year' => $this->getCurrentAcademicYear(),
                'semester' => '1st Semester',
                'user_id' => $creatorId,
                'status' => self::STATUS_PROPOSAL,
                'adviser_id' => null,
                'preferred_adviser_ids' => $preferredAdviserIds,
            ]);

            foreach ($preferredAdviserIds as $index => $adviserId) {
                ProjectPreferredAdviser::create([
                    'project_id' => $project->id,
                    'adviser_id' => $adviserId,
                    'preference_order' => $index + 1,
                ]);

                Notification::create([
                    'user_id' => $adviserId,
                    'title' => $index === 0
                        ? 'Priority Adviser Selection'
                        : 'Preferred Adviser Selection',
                    'message' => $user->first_name . ' ' . $user->last_name .
                        ($index === 0
                            ? ' selected you as priority adviser.'
                            : ' selected you as preferred adviser #' . ($index + 1) . '.'),
                    'type' => $index === 0
                        ? 'priority_adviser_added'
                        : 'preferred_adviser_added',
                    'reference_id' => $project->id,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);
            }

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
                        ' added you to the topic proposal.',
                    'type' => 'researcher_added',
                    'reference_id' => $project->id,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);
            }

            $focalPersonIds = User::query()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Focal Person');
                })
                ->where('department_id', $user->department_id)
                ->pluck('id')
                ->unique()
                ->values();

            foreach ($focalPersonIds as $focalPersonId) {
                Notification::create([
                    'user_id' => (int) $focalPersonId,
                    'title' => 'New Topic Proposal Submitted',
                    'message' => $user->first_name . ' ' . $user->last_name .
                        ' submitted 3 proposal titles, files, and 3 preferred advisers.',
                    'type' => 'project_created',
                    'reference_id' => $project->id,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);
            }

            DB::commit();

            return redirect()
                ->route('student.dashboard')
                ->with('success', 'Topic proposal submitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    private function getCurrentAcademicYear(): string
    {
        $year = now()->year;
        $month = now()->month;

        return $month >= 8
            ? "$year-" . ($year + 1)
            : ($year - 1) . "-$year";
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