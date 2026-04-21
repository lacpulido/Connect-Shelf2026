<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StudentsSubmissionController extends Controller
{

    public function index(Project $project): Response
    {
        $this->authorizeAdviser($project);

        $documents = ProjectDocument::with([
                'comments' => fn($q) => $q->latest()->limit(1),
            ])
            ->where('project_id', $project->id)
            ->latest()
            ->get()
            ->groupBy(fn($doc) => $doc->parent_id ?? $doc->id)
            ->map(fn($versions) => $versions->map(fn($doc) => $this->formatDocument($doc)))
            ->values();

        return Inertia::render('Faculty/ViewProject', [
            'project' => [
                'slug'  => $project->slug,
                'title' => $project->title,
            ],
            'documents' => $documents,
        ]);
    }
    public function facultyShow(Project $project, string $folder, string $document): Response
    {
        $this->authorizeAdviser($project);
        $slug = "{$folder}/{$document}";
        $currentDocument = ProjectDocument::where('slug', $slug)
            ->where('project_id', $project->id)
            ->firstOrFail();

        $rootId = $this->findRootId($currentDocument);

        $versions = ProjectDocument::with('comments.adviser')
            ->where(fn($q) => $q->where('id', $rootId)->orWhere('parent_id', $rootId))
            ->orderByDesc('version')
            ->get()
            ->map(fn($doc) => $this->formatDocument($doc, withAdviser: true));

        return Inertia::render('Faculty/ViewSubmissions', [
            'submission' => [
                'id'           => $currentDocument->id,
                'title'        => $currentDocument->title,
                'project_slug' => $project->slug,
                'versions'     => $versions,
            ],
        ]);
    }
    private function authorizeAdviser(Project $project): void
    {
        abort_if((int) $project->adviser_id !== (int) Auth::id(), 403);
    }
    private function findRootId(ProjectDocument $document): int
    {
       
        if (!$document->parent_id) {
            return $document->id;
        }
        return ProjectDocument::whereNull('parent_id')
            ->where(function ($query) use ($document) {
                $query->where('id', $document->parent_id)
                    ->orWhereIn('id', function ($sub) use ($document) {
                        $sub->select('parent_id')
                            ->from('project_documents')
                            ->where('id', $document->parent_id)
                            ->whereNotNull('parent_id');
                    });
            })
            ->value('id') ?? $document->parent_id;
    }

    private function formatDocument(ProjectDocument $doc, bool $withAdviser = false): array
    {
        return [
            'id'         => $doc->id,
            'slug'       => $doc->slug,
            'title'      => $doc->title,
            'filename'   => basename((string) $doc->filename),
            'status'     => $doc->status,
            'version'    => $doc->version,
            'is_current' => $doc->is_current,
            'created_at' => $doc->created_at,
            'file_url'   => route('faculty.documents.view', $doc->slug),
            'comments'   => $doc->comments->map(
                fn($c) => $this->formatComment($c, $withAdviser)
            ),
        ];
    }
    private function formatComment($comment, bool $withAdviser = false): array
    {
        $formatted = [
            'id'       => $comment->id,
            'comment'  => $comment->comment,
            'decision' => $comment->decision,
        ];

        if ($withAdviser) {
            $formatted['adviser'] = [
                'first_name' => $comment->adviser?->first_name,
                'last_name'  => $comment->adviser?->last_name,
            ];
        }

        return $formatted;
    }
}