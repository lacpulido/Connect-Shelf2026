<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ProjectDocument;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReviewDocumentController extends Controller
{
    // ─── Public Methods ──────────────────────────────────────────────────────

    public function storeComment(Request $request, string $document): RedirectResponse
    {
        $projectDocument = $this->findDocument($document);

        $validated = $request->validate([
            'comment'  => ['nullable', 'string'],
            'decision' => ['required', 'in:approved,needs_revision'],
        ]);

        Comment::create([
            'project_document_id' => $projectDocument->id,
            'adviser_id'          => Auth::id(),
            'comment'             => $validated['comment'] ?? '',
            'decision'            => $validated['decision'],
        ]);

        $projectDocument->update(['status' => $validated['decision']]);

        $this->notifyStudents($projectDocument, $validated['decision']);

        return back()->with('success', 'Review submitted successfully.');
    }

    public function view(string $document): BinaryFileResponse
    {
        $projectDocument = $this->findDocument($document);

        // Mark as under review when first opened
        if ($projectDocument->status === 'submitted') {
            $projectDocument->update(['status' => 'under_review']);
        }

        return $this->serveFile($projectDocument, inline: true);
    }

    public function download(string $document): BinaryFileResponse
    {
        return $this->serveFile($this->findDocument($document), inline: false);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Find a document by slug and verify the authenticated faculty owns it.
     */
    private function findDocument(string $slug): ProjectDocument
    {
        $document = ProjectDocument::with('project')
            ->where('slug', $slug)
            ->firstOrFail();

        abort_if(!$document->project, 404, 'Project not found.');
        abort_if((int) $document->project->adviser_id !== (int) Auth::id(), 403, 'Unauthorized access.');

        return $document;
    }

    /**
     * Serve a document file either inline (view) or as a download.
     */
    private function serveFile(ProjectDocument $document, bool $inline): BinaryFileResponse
    {
        $fullPath = $this->getFilePath($document);

        abort_unless(file_exists($fullPath), 404, 'Document file not found.');

        $filename = basename((string) $document->filename);

        if ($inline) {
            return response()->file($fullPath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
                'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'              => 'no-cache',
            ]);
        }

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    private function notifyStudents(ProjectDocument $projectDocument, string $decision): void
    {
        $project = $projectDocument->project;

        if (!$project) return;

        $isApproved = $decision === 'approved';

        $title   = $isApproved ? 'Document Approved'        : 'Document Needs Revision';
        $message = $isApproved
            ? "Your document '{$projectDocument->title}' has been approved."
            : "Your document '{$projectDocument->title}' needs revision.";

        $studentIds = collect([$project->user_id])
            ->merge($project->researchers()->pluck('users.id'))
            ->filter()
            ->unique();

        foreach ($studentIds as $studentId) {
            NotificationService::send(
                userId:        $studentId,
                title:         $title,
                message:       $message,
                type:          strtoupper($decision),
                referenceId:   $projectDocument->id,
                referenceType: 'project_document'
            );
        }
    }

    /**
     * Get the full storage path for a document file.
     */
    private function getFilePath(ProjectDocument $document): string
    {
        return storage_path(
            "app/private/project-documents/{$document->project_id}/{$document->filename}"
        );
    }
}