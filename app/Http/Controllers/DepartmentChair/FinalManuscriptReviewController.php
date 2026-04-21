<?php

namespace App\Http\Controllers\DepartmentChair;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectManuscript;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FinalManuscriptReviewController extends Controller
{
    public function approve(int $id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $manuscript = ProjectManuscript::findOrFail($id);

            if ($manuscript->status === 'approved') {
                return back()->with('info', 'Manuscript has already been approved.');
            }

            // Approve manuscript first
            $manuscript->update([
                'status' => 'approved',
            ]);

            // Get BOTH student + adviser BEFORE project delete
            [$studentId, $adviserId] = $this->completeAndArchiveProject($manuscript->project_id);

            // Soft delete manuscript too
            $manuscript->delete();

            DB::commit();

            // Re-sync searchable record after soft delete
            $manuscript->searchable();

            // Notify Student
            if ($studentId) {
                $this->notifyStudent($studentId, $manuscript);
            }

            // Notify Adviser
            if ($adviserId) {
                $this->notifyAdviser($adviserId, $manuscript);
            }

            return back()->with(
                'success',
                'Manuscript approved, archived, project completed, and adviser notified.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * COMPLETE + ARCHIVE PROJECT
     * Returns: [student_id, adviser_id]
     */
    private function completeAndArchiveProject(?int $projectId): array
    {
        if (!$projectId) {
            return [null, null];
        }

        $project = Project::find($projectId);

        if (!$project) {
            return [null, null];
        }

        $studentId = $project->user_id;
        $adviserId = $project->adviser_id;

        $project->update([
            'status'       => 'Completed',
            'completed_at' => now(),
        ]);

        // Soft delete project
        $project->delete();

        return [$studentId, $adviserId];
    }

    private function notifyStudent(int $userId, ProjectManuscript $manuscript): void
    {
        NotificationService::send(
            $userId,
            'Manuscript Approved',
            'Congratulations! Your manuscript "' . $manuscript->title . '" has been approved.',
            'manuscript_approved',
            $manuscript->id,
            'manuscript'
        );
    }

    private function notifyAdviser(int $adviserId, ProjectManuscript $manuscript): void
    {
        NotificationService::send(
            $adviserId,
            'Manuscript Approved (Adviser)',
            'The manuscript of "' . $manuscript->title . '" has been approved.',
            'manuscript_approved_adviser',
            $manuscript->id,
            'manuscript'
        );
    }

    public function view(Request $request, int $id)
    {
        $manuscript = ProjectManuscript::withTrashed()->findOrFail($id);
        $filePath   = 'final_manuscripts/' . $manuscript->filename;

        abort_unless(Storage::exists($filePath), 404, 'File not found.');

        $fullPath = Storage::path($filePath);

        return $request->has('download')
            ? response()->download($fullPath, $manuscript->filename)
            : response()->file($fullPath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $manuscript->filename . '"',
            ]);
    }
}