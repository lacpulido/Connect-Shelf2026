<?php

namespace App\Http\Controllers\faculty;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdviserRequestController extends Controller
{
    public function index(): Response
    {
        $facultyId = Auth::id();

        $projects = Project::with([
                'user.department',
                'researchers',
                'adviser',
                'preferredAdvisers',
            ])
            ->whereNotNull('proposal_titles')
            ->whereHas('preferredAdvisers', function ($query) use ($facultyId) {
                $query->where('users.id', $facultyId)
                    ->where('project_preferred_advisers.is_shared', true);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Project $project) use ($facultyId) {
                $preferredAdviser = $project->preferredAdvisers
                    ->firstWhere('id', $facultyId);

                $proposalTitles = $this->toArrayValue($project->proposal_titles);
                $proposalFiles = $this->toArrayValue($project->proposal_files);
                $proposalOriginalNames = $this->toArrayValue($project->proposal_original_names);

                return [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                    'status' => $project->status,

                    'adviser_status' => $preferredAdviser?->pivot?->status ?? 'pending',
                    'is_shared' => (bool) ($preferredAdviser?->pivot?->is_shared ?? false),
                    'shared_at' => $preferredAdviser?->pivot?->shared_at,

                    'has_accepted_adviser' => ! is_null($project->adviser_id),
                    'accepted_adviser_id' => $project->adviser_id,
                    'accepted_adviser_name' => $project->adviser
                        ? trim(($project->adviser->first_name ?? '') . ' ' . ($project->adviser->last_name ?? ''))
                        : null,

                    'project_type' => $project->project_type,
                    'academic_year' => $project->academic_year,
                    'semester' => $project->semester,

                    'proposal_titles' => $proposalTitles,
                    'proposal_files' => $proposalFiles,
                    'proposal_original_names' => $proposalOriginalNames,

                    'proposal_file' => $project->proposal_file,
                    'proposal_original_name' => $project->proposal_original_name,

                    'preferred_adviser_rank' => $preferredAdviser?->pivot?->preference_order,
                    'approved_proposal_index' => $project->approved_proposal_index,

                    'student' => $project->user ? [
                        'id' => $project->user->id,
                        'name' => trim(($project->user->first_name ?? '') . ' ' . ($project->user->last_name ?? '')),
                        'department' => $project->user->department ? [
                            'name' => $project->user->department->name,
                        ] : null,
                    ] : null,

                    'researchers' => $project->researchers->map(function ($researcher) {
                        return [
                            'id' => $researcher->id,
                            'name' => trim(($researcher->first_name ?? '') . ' ' . ($researcher->last_name ?? '')),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('Faculty/AdviserRequests', [
            'projects' => $projects,
        ]);
    }

    public function accept(Request $request, Project $project): RedirectResponse
    {
        $facultyId = Auth::id();

        $isSharedToFaculty = $project->preferredAdvisers()
            ->where('users.id', $facultyId)
            ->wherePivot('is_shared', true)
            ->exists();

        abort_unless($isSharedToFaculty, 403);

        if (! is_null($project->adviser_id) && (int) $project->adviser_id !== (int) $facultyId) {
            return back()->with('error', 'This project already has an accepted adviser.');
        }

        $proposalTitles = $this->toArrayValue($project->proposal_titles);

        $validated = $request->validate([
            'approved_proposal_index' => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        $approvedIndex = (int) $validated['approved_proposal_index'];

        if (! array_key_exists($approvedIndex, $proposalTitles) || blank($proposalTitles[$approvedIndex])) {
            return back()->withErrors([
                'approved_proposal_index' => 'Please select a valid topic proposal.',
            ]);
        }

        $approvedTitle = $proposalTitles[$approvedIndex];

        $project->preferredAdvisers()->updateExistingPivot($facultyId, [
            'status' => 'accepted',
        ]);

        $project->update([
            'title' => $approvedTitle,
            'approved_proposal_index' => $approvedIndex,
            'adviser_id' => $facultyId,
            'adviser_status' => 'accepted',
            'status' => 'ongoing',
        ]);

        $this->notifyStudents(
            $project,
            'Adviser Accepted',
            'One of your selected advisers accepted your topic proposal.'
        );

        return back()->with('success', 'Project accepted successfully.');
    }

    public function decline(Project $project): RedirectResponse
    {
        $facultyId = Auth::id();

        $isSharedToFaculty = $project->preferredAdvisers()
            ->where('users.id', $facultyId)
            ->wherePivot('is_shared', true)
            ->exists();

        abort_unless($isSharedToFaculty, 403);

        if (! is_null($project->adviser_id) && (int) $project->adviser_id === (int) $facultyId) {
            return back()->with('error', 'You already accepted this project. You cannot decline it now.');
        }

        $project->preferredAdvisers()->updateExistingPivot($facultyId, [
            'status' => 'declined',
        ]);

        $this->notifyStudents(
            $project,
            'Adviser Declined',
            'One of your selected advisers declined your topic proposal.'
        );

        return back()->with('success', 'Project declined successfully.');
    }

    private function notifyStudents(Project $project, string $title, string $message): void
    {
        $project->loadMissing(['researchers']);

        $studentIds = collect([$project->user_id])
            ->merge($project->researchers->pluck('id'))
            ->filter()
            ->unique();

        foreach ($studentIds as $studentId) {
            NotificationService::send(
                $studentId,
                $title,
                $message,
                'adviser_request_response',
                $project->id,
                'project'
            );
        }
    }

    private function toArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value) && filled($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_values($decoded) : [];
        }

        return [];
    }
}