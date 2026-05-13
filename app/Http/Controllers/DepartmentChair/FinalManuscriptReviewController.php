<?php

namespace App\Http\Controllers\DepartmentChair;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectManuscript;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinalManuscriptReviewController extends Controller
{
    public function review(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,revise'],
            'revision_comment' => ['nullable', 'string', 'max:5000', 'required_if:decision,revise'],
        ]);

        if ($validated['decision'] === 'approve') {
            return $this->approve($id);
        }

        DB::beginTransaction();

        try {
            $manuscript = ProjectManuscript::findOrFail($id);

            if ($manuscript->status === 'approved') {
                DB::rollBack();

                return back()->with('error', 'Approved manuscript cannot be requested for revision.');
            }

            $updated = ProjectManuscript::where('id', $id)->update([
                'status' => 'request_revision',
                'revision_comment' => $validated['revision_comment'],
                'updated_at' => now(),
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update manuscript revision status.');
            }

            DB::commit();

            return back()->with('success', 'Revision requested successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Final manuscript revision request failed.', [
                'manuscript_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function approve(int $id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $manuscript = ProjectManuscript::findOrFail($id);

            if ($manuscript->status === 'approved') {
                DB::commit();

                return back()->with('info', 'Manuscript has already been approved.');
            }

            $updated = ProjectManuscript::where('id', $id)->update([
                'status' => 'approved',
                'revision_comment' => null,
                'updated_at' => now(),
            ]);

            if (! $updated) {
                throw new \Exception('Failed to update manuscript status.');
            }

            $manuscript = ProjectManuscript::findOrFail($id);

            if ($manuscript->status !== 'approved') {
                throw new \Exception('Manuscript status is still not approved after update.');
            }

            [$studentId, $adviserId] = $this->completeProject($manuscript->project_id);

            DB::commit();

            if (method_exists($manuscript, 'searchable')) {
                $manuscript->searchable();
            }

            if ($studentId) {
                $this->notifyStudent($studentId, $manuscript);
            }

            if ($adviserId) {
                $this->notifyAdviser($adviserId, $manuscript);
            }

            return back()->with('success', 'Manuscript approved successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Final manuscript approval failed.', [
                'manuscript_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    private function completeProject(?int $projectId): array
    {
        if (! $projectId) {
            return [null, null];
        }

        $project = Project::find($projectId);

        if (! $project) {
            return [null, null];
        }

        $studentId = $project->user_id;
        $adviserId = $project->adviser_id;

        $project->update([
            'status' => 'Completed',
            'completed_at' => now(),
        ]);

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
            'Manuscript Approved ',
            'The manuscript of "' . $manuscript->title . '" has been approved.',
            'manuscript_approved_adviser',
            $manuscript->id,
            'manuscript'
        );
    }

    public function view(Request $request, int $id)
    {
        $manuscript = ProjectManuscript::withTrashed()->findOrFail($id);
        $filePath = 'final_manuscripts/' . $manuscript->filename;

        abort_unless(Storage::exists($filePath), 404, 'File not found.');

        $fullPath = Storage::path($filePath);

        return $request->has('download')
            ? response()->download($fullPath, $manuscript->filename)
            : response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $manuscript->filename . '"',
            ]);
    }
}