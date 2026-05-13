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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SubmitFinalManuscriptController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $project = $this->getStudentProject($userId);

        if (! $project) {
            return Inertia::render('Student/SubmitFinalManuscript', [
                'project' => null,
                'manuscriptSubmitted' => false,
                'manuscriptStatus' => null,
                'manuscriptFileName' => null,
                'revisionComments' => null,
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
            'manuscriptSubmitted' => (bool) $manuscript,
            'manuscriptStatus' => $manuscript->status ?? null,
            'manuscriptFileName' => $manuscript->original_filename ?? $manuscript->filename ?? null,
            'revisionComments' => $manuscript->revision_comment
                ?? $manuscript->revision_comments
                ?? $manuscript->comments
                ?? $manuscript->remarks
                ?? null,
            'canSubmitFinalManuscript' => (bool) $approvedDocument,
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

            if (! $user) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You must be logged in to submit a final manuscript.',
                ]);
            }

            $project = $this->getStudentProject($user->id);

            if (! $project) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You are not assigned to any project.',
                ]);
            }

            $approvedDocument = $this->getApprovedManuscriptDocument($project->id);

            if (! $approvedDocument) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'You can only submit the final manuscript after your Manuscript document is approved by your adviser.',
                ]);
            }

            $existingManuscript = ProjectManuscript::where('project_id', $project->id)
                ->latest()
                ->first();

            $existingStatus = strtolower(trim((string) ($existingManuscript->status ?? '')));

            $isRevision = $existingManuscript && in_array($existingStatus, [
                'revision',
                'request_revision',
                'requested_revision',
                'revise',
            ], true);

            $validated = $request->validate([
                'title' => [$isRevision ? 'nullable' : 'required', 'string', 'max:255'],
                'abstract' => [$isRevision ? 'nullable' : 'required', 'string'],
                'manuscript' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            ]);

            if ($existingManuscript && ! $isRevision) {
                DB::rollBack();

                return back()->withErrors([
                    'error' => 'Final manuscript already submitted.',
                ]);
            }

            $file = $request->file('manuscript');

            if (! $file || ! $file->isValid()) {
                DB::rollBack();

                return back()->withErrors([
                    'manuscript' => 'Uploaded file is invalid. Please try again.',
                ]);
            }

            Storage::disk('local')->makeDirectory('final_manuscripts');

            $originalName = $file->getClientOriginalName();
            $safeOriginalName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

            $storedFileName = $this->getAvailableFileName(
                disk: 'local',
                directory: 'final_manuscripts',
                fileName: $safeOriginalName
            );

            $path = $file->storeAs('final_manuscripts', $storedFileName, 'local');

            if (! $path || ! Storage::disk('local')->exists($path)) {
                throw new \Exception('Failed to upload manuscript file.');
            }

            if ($isRevision) {
                if (
                    $existingManuscript->filename &&
                    Storage::disk('local')->exists('final_manuscripts/' . $existingManuscript->filename)
                ) {
                    Storage::disk('local')->delete('final_manuscripts/' . $existingManuscript->filename);
                }

                $existingManuscript->update([
                    'filename' => $storedFileName,
                    'original_filename' => $originalName,
                    'status' => 'resubmitted',
                    'revision_comment' => null,
                    'updated_at' => now(),
                ]);

                $manuscript = $existingManuscript;
                $successMessage = 'Final manuscript resubmitted successfully.';
                $notificationTitle = 'Final Manuscript Resubmitted';
                $notificationAction = 'resubmitted';
            } else {
                $manuscript = ProjectManuscript::create([
                    'project_id' => $project->id,
                    'title' => $validated['title'],
                    'abstract' => $validated['abstract'],
                    'filename' => $storedFileName,
                    'original_filename' => $originalName,
                    'status' => 'pending',
                ]);

                $successMessage = 'Final manuscript submitted successfully.';
                $notificationTitle = 'Final Manuscript Submitted';
                $notificationAction = 'submitted';
            }

            $submitterName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

            if ($submitterName === '') {
                $submitterName = $user->name ?? 'A student';
            }

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
                    'title' => $notificationTitle,
                    'message' => $submitterName . ' ' . $notificationAction . ' the final manuscript for project "' . $project->title . '".',
                    'type' => 'final_manuscript_submitted',
                    'reference_id' => $manuscript->id,
                    'reference_type' => ProjectManuscript::class,
                    'status' => 'UNREAD',
                ]);
            }

            DB::commit();

            return back()->with('success', $successMessage);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Final manuscript submit failed.', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => 'Failed to submit final manuscript. ' . $e->getMessage(),
            ]);
        }
    }

    private function getAvailableFileName(string $disk, string $directory, string $fileName): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $finalFileName = $fileName;
        $counter = 1;

        while (Storage::disk($disk)->exists($directory . '/' . $finalFileName)) {
            $finalFileName = $name . '_' . $counter . ($extension ? '.' . $extension : '');
            $counter++;
        }

        return $finalFileName;
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