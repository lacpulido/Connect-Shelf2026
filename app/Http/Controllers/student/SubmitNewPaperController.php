<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubmitNewPaperController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Student/Submissions');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $user = Auth::user();
        $userId = $user->id;

        $project = Project::with(['researchers', 'adviser'])
            ->where('user_id', $userId)
            ->orWhereHas('researchers', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->firstOrFail();

        $originalName = pathinfo(
            $validated['document']->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $extension = $validated['document']->getClientOriginalExtension();
        $fileName = $originalName . '-v1.' . $extension;

        $validated['document']->storeAs(
            "project-documents/{$project->id}",
            $fileName,
            'local'
        );

        $document = $project->documents()->create([
            'title' => $validated['title'],
            'filename' => $fileName,
            'status' => 'submitted',
            'version' => 1,
            'is_current' => true,
        ]);

        $submittedBy = trim(
            ($user->first_name ?? '') . ' ' .
            ($user->middle_name ?? '') . ' ' .
            ($user->last_name ?? '')
        );

        $projectTitle = $project->title ?? 'Untitled Project';

        // ✅ CLEAN NOTIFICATION FORMAT
        if ($project->adviser_id) {
            NotificationService::send(
    userId: $project->adviser_id,
    title: 'New Paper Submission',
    message:
        "New paper submitted for: {$projectTitle}\n\n" .
        "Title: {$document->title}\n" .
        "Submitted by: {$submittedBy}",
    type: 'PAPER_SUBMITTED',
    referenceId: $document->id,
    referenceType: 'project_document'
);
        }

        return redirect()
            ->route('student.submissions')
            ->with('success', 'Paper submitted successfully.');
    }

    public function resubmit(Request $request, $submission)
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);
        $user = Auth::user();
        $oldDocument = ProjectDocument::with('project')->findOrFail($submission);
        $root = $oldDocument;
        while ($root->parent_id) {
            $root = ProjectDocument::find($root->parent_id);
        }

        $rootId = $root->id;
        $latestVersion = ProjectDocument::where(function ($query) use ($rootId) {
            $query->where('id', $rootId)
                ->orWhere('parent_id', $rootId);
        })->max('version');
        ProjectDocument::where(function ($query) use ($rootId) {
            $query->where('id', $rootId)
                ->orWhere('parent_id', $rootId);
        })->update([
            'is_current' => false,
        ]);
        $originalName = pathinfo(
            $validated['document']->getClientOriginalName(),
            PATHINFO_FILENAME
        );
        $extension = $validated['document']->getClientOriginalExtension();
        $newVersion = $latestVersion + 1;
        $fileName = $originalName . '-v' . $newVersion . '.' . $extension;
        $validated['document']->storeAs(
            "project-documents/{$oldDocument->project_id}",
            $fileName,
            'local'
        );
        $newDocument = ProjectDocument::create([
            'project_id' => $oldDocument->project_id,
            'title' => $oldDocument->title,
            'filename' => $fileName,
            'status' => 'under_review',
            'version' => $newVersion,
            'is_current' => true,
            'parent_id' => $rootId,
        ]);
        $project = $oldDocument->project;
        $submittedBy = trim(
            ($user->first_name ?? '') . ' ' .
            ($user->middle_name ?? '') . ' ' .
            ($user->last_name ?? '')
        );
        $projectTitle = $project->title ?? 'Untitled Project';
        if ($project && $project->adviser_id) {
            NotificationService::send(
                userId: $project->adviser_id,
                title: 'Paper Resubmitted',
                message: "Paper Resubmitted\n\n"
                    . "Project: {$projectTitle}\n"
                    . "Title: {$newDocument->title}\n"
                    . "Submitted by: {$submittedBy}",
                type: 'PAPER_RESUBMITTED',
                referenceId: $newDocument->id,
                referenceType: 'project_document'
            );
        }

        return back()->with('success', 'Paper resubmitted successfully.');
    }
}