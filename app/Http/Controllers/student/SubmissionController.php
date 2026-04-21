<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    protected function findUserProject(int $userId): ?Project
    {
        return Project::withTrashed()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->latest('id')
            ->first();
    }

    protected function userCanAccessProject(Project $project, int $userId): bool
    {
        $project->loadMissing('researchers');

        $isOwner = (int) $project->user_id === (int) $userId;
        $isResearcher = $project->researchers->contains('id', $userId);

        return $isOwner || $isResearcher;
    }

    protected function ensureProjectIsNotCompleted(Project $project): void
    {
        $isCompleted = strtolower((string) $project->status) === 'completed'
            || !is_null($project->completed_at);

        if ($isCompleted) {
            abort(403, 'This project is already completed. Uploading, resubmitting, and removing files are no longer allowed.');
        }
    }

    public function index()
    {
        $userId = Auth::id();

        $project = $this->findUserProject($userId);

        if (!$project) {
            return Inertia::render('Student/Submissions', [
                'project' => null,
                'documents' => [],
            ]);
        }

        $documents = ProjectDocument::where('project_id', $project->id)
            ->with([
                'comments' => function ($query) {
                    $query->latest()
                        ->limit(1)
                        ->with('adviser:id,first_name,last_name');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($doc) {
                return $doc->parent_id ?? $doc->id;
            })
            ->map(function ($versions) {
                return $versions->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'slug' => $doc->slug,
                        'folder_id' => $doc->folder_id,
                        'title' => $doc->title,
                        'filename' => basename($doc->filename),
                        'status' => $doc->status,
                        'version' => $doc->version,
                        'is_current' => $doc->is_current,
                        'created_at' => $doc->created_at,
                        'file_url' => route('student.submissions.download', $doc->slug),
                        'comments' => $doc->comments->map(function ($comment) {
                            return [
                                'id' => $comment->id,
                                'comment' => $comment->comment ?? '',
                                'decision' => $comment->decision ?? '',
                                'adviser' => [
                                    'first_name' => $comment->adviser->first_name ?? '',
                                    'last_name' => $comment->adviser->last_name ?? '',
                                ],
                            ];
                        })->values(),
                    ];
                })->values();
            })
            ->values();

        return Inertia::render('Student/Submissions', [
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'adviser_id' => $project->adviser_id,
                'status' => $project->status,
                'completed_at' => $project->completed_at,
            ],
            'documents' => $documents,
        ]);
    }

    public function show(Project $project, $folder, $document)
    {
        $userId = Auth::id();

        $project = Project::withTrashed()
            ->with('researchers')
            ->findOrFail($project->id);

        if (!$this->userCanAccessProject($project, $userId)) {
            abort(403, 'You are not allowed to view these submissions.');
        }

        $slug = $folder . '/' . $document;

        $currentDocument = ProjectDocument::where('project_id', $project->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $allVersions = ProjectDocument::where('project_id', $project->id)
            ->where('title', $currentDocument->title)
            ->with([
                'comments' => function ($query) {
                    $query->latest()
                        ->limit(1)
                        ->with('adviser:id,first_name,last_name');
                },
            ])
            ->orderByDesc('version')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'filename' => basename($doc->filename),
                    'file_url' => route('student.submissions.download', $doc->slug),
                    'version' => $doc->version,
                    'status' => $doc->status,
                    'created_at' => $doc->created_at,
                    'comments' => $doc->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'comment' => $comment->comment ?? '',
                            'decision' => $comment->decision ?? '',
                            'adviser' => [
                                'first_name' => $comment->adviser->first_name ?? '',
                                'last_name' => $comment->adviser->last_name ?? '',
                            ],
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('Student/ViewSubmissions', [
            'submission' => [
                'id' => $currentDocument->id,
                'title' => $currentDocument->title,
                'created_at' => $currentDocument->created_at,
                'versions' => $allVersions,
            ],
        ]);
    }

    public function download($slug)
    {
        $userId = Auth::id();

        $document = ProjectDocument::where('slug', $slug)->firstOrFail();

        $project = Project::withTrashed()
            ->with('researchers')
            ->findOrFail($document->project_id);

        if (!$this->userCanAccessProject($project, $userId)) {
            abort(403);
        }

        $filePath = storage_path(
            'app/private/project-documents/' .
            $document->project_id . '/' .
            $document->filename
        );

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->filename . '"',
        ]);
    }

    public function destroy($id)
    {
        $userId = Auth::id();

        $document = ProjectDocument::with([
            'project' => function ($query) {
                $query->withTrashed();
            },
            'project.researchers',
        ])->findOrFail($id);

        $project = $document->project;

        if (!$project) {
            abort(404, 'Project not found.');
        }

        if (!$this->userCanAccessProject($project, $userId)) {
            abort(403, 'You are not allowed to delete this file.');
        }

        $this->ensureProjectIsNotCompleted($project);

        $filePath = storage_path(
            'app/private/project-documents/' .
            $document->project_id . '/' .
            $document->filename
        );

        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $rootId = $document->parent_id ?: $document->id;
        $wasCurrent = (bool) $document->is_current;

        $document->delete();

        if ($wasCurrent) {
            $remainingVersions = ProjectDocument::where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('parent_id', $rootId);
            })
            ->orderByDesc('version')
            ->get();

            if ($remainingVersions->isNotEmpty()) {
                ProjectDocument::where(function ($query) use ($rootId) {
                    $query->where('id', $rootId)
                        ->orWhere('parent_id', $rootId);
                })->update([
                    'is_current' => false,
                ]);

                $latest = $remainingVersions->first();
                $latest->update([
                    'is_current' => true,
                ]);
            }
        }

        return back()->with('success', 'File removed successfully.');
    }
}