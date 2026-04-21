<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectManuscript;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubmitFinalManuscriptController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $project = Project::with('department')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->latest()
            ->first();

        if (!$project) {
            return Inertia::render('Student/SubmitFinalManuscript', [
                'project' => null,
                'manuscriptSubmitted' => false,
                'manuscriptStatus' => null,
                'manuscriptFileName' => null,
            ]);
        }

        $manuscript = ProjectManuscript::where('project_id', $project->id)
            ->latest()
            ->first();

        return Inertia::render('Student/SubmitFinalManuscript', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'academic_year' => $project->academic_year,
                'department' => optional($project->department)->name,
                'project_type' => $project->project_type,
                'adviser_id' => $project->adviser_id,
            ],
            'manuscriptSubmitted' => $manuscript ? true : false,
            'manuscriptStatus' => $manuscript->status ?? null,
            'manuscriptFileName' => $manuscript->original_filename ?? $manuscript->filename ?? null,
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $userId = $user->id;

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'abstract' => 'required|string',
                'manuscript' => 'required|file|mimes:pdf,doc,docx|max:10240',
            ]);

            $project = Project::with(['department', 'user', 'researchers'])
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('researchers', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->latest()
                ->first();

            if (!$project) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You are not assigned to any project.',
                ]);
            }

            if (ProjectManuscript::where('project_id', $project->id)->exists()) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'Final manuscript already submitted.',
                ]);
            }

            $file = $request->file('manuscript');
            $originalName = $file->getClientOriginalName();

            $storedFileName = time() . '_' . $originalName;
            $file->storeAs('final_manuscripts', $storedFileName, 'local');

            $manuscript = ProjectManuscript::create([
                'project_id' => $project->id,
                'title' => $validated['title'],
                'abstract' => $validated['abstract'],
                'filename' => $storedFileName,
                'original_filename' => $originalName,
                'status' => 'pending',
            ]);

            $submitterName = trim($user->first_name . ' ' . $user->last_name);

            $departmentChairs = User::query()
                ->where('department_id', $project->department_id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Department Chair');
                })
                ->get();

            foreach ($departmentChairs as $chair) {
                Notification::create([
                    'user_id' => $chair->id,
                    'title' => 'Final Manuscript Submitted',
                    'message' => $submitterName . ' submitted the final manuscript for project "' . $project->title . '".',
                    'type' => 'final_manuscript_submitted',
                    'reference_id' => $manuscript->id,
                    'reference_type' => ProjectManuscript::class,
                    'status' => 'UNREAD',
                ]);
            }

            DB::commit();

            return back()->with('success', 'Final manuscript submitted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
}