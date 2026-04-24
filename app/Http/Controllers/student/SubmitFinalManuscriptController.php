<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectManuscript;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubmitFinalManuscriptController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $project = $this->getStudentProject($userId);

        if (!$project) {
            return Inertia::render('Student/SubmitFinalManuscript', [
                'project' => null,
                'manuscriptSubmitted' => false,
                'manuscriptStatus' => null,
                'manuscriptFileName' => null,
                'canSubmitFinalManuscript' => false,
                'approvedManuscriptDocument' => null,
            ]);
        }

        $manuscript = ProjectManuscript::where('project_id', $project->id)
            ->latest()
            ->first();

        $approvedDocument = $this->getApprovedManuscriptDocument($project->id);

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
            'canSubmitFinalManuscript' => $approvedDocument ? true : false,
            'approvedManuscriptDocument' => $approvedDocument ? [
                'id' => $approvedDocument->id,
                'title' => $approvedDocument->title,
                'filename' => $approvedDocument->filename,
                'status' => $approvedDocument->status,
            ] : null,
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

            $project = $this->getStudentProject($userId);

            if (!$project) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You are not assigned to any project.',
                ]);
            }

            $approvedDocument = $this->getApprovedManuscriptDocument($project->id);

            if (!$approvedDocument) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You can only submit the final manuscript after your Manuscript document is approved by your adviser.',
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

            $submitterName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            $departmentChairs = User::query()
                ->where('department_id', $project->department_id)
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', [
                        'Department Chair',
                        'Department ChairPerson',
                    ]);
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

    private function getStudentProject(int $userId): ?Project
    {
        return Project::with(['department', 'user', 'researchers'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->latest()
            ->first();
    }

    private function getApprovedManuscriptDocument(int $projectId): ?ProjectDocument
    {
        return ProjectDocument::query()
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->where('title', 'like', '%manuscript%')
                    ->orWhere('slug', 'like', '%manuscript%');
            })
            ->when(
                ProjectDocument::query()
                    ->where('project_id', $projectId)
                    ->whereNotNull('is_current')
                    ->exists(),
                fn ($query) => $query->where('is_current', true)
            )
            ->latest()
            ->first();
    }
}