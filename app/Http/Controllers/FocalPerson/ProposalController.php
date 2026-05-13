<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProposalController extends Controller
{
    public function index(): Response
    {
        $authUser = Auth::user();
        $departmentId = $authUser->department_id;
        $allowedProjectType = $this->getAllowedProjectTypeByDepartment($authUser?->department?->name);

        $projects = Project::with([
                'user.department',
                'preferredAdvisers.department',
                'researchers',
            ])
            ->whereNotNull('proposal_titles')
            ->whereHas('user', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->when($allowedProjectType, function ($query) use ($allowedProjectType) {
                $query->where('project_type', $allowedProjectType);
            })
            ->orderByDesc('is_resubmitted')
            ->orderByDesc('resubmitted_at')
            ->orderByDesc('created_at')
            ->get();

        $mappedProjects = $projects
            ->map(function (Project $project) {
                $proposalTitles = $this->toArrayValue($project->proposal_titles);
                $proposalFiles = $this->toArrayValue($project->proposal_files);
                $proposalOriginalNames = $this->toArrayValue($project->proposal_original_names);

                return [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'title' => $project->title,
                    'project_type' => $project->project_type,
                    'academic_year' => $project->academic_year,
                    'semester' => $project->semester,
                    'status' => $project->status,

                    'is_resubmitted' => (bool) $project->is_resubmitted,
                    'resubmitted_at' => $project->resubmitted_at
                        ? $project->resubmitted_at->format('M d, Y h:i A')
                        : null,

                    'proposal_titles' => $proposalTitles,
                    'proposal_files' => $proposalFiles,
                    'proposal_original_names' => $proposalOriginalNames,
                    'proposal_file' => $project->proposal_file,
                    'proposal_original_name' => $project->proposal_original_name,
                    'approved_proposal_index' => $project->approved_proposal_index,

                    'student' => $project->user ? [
                        'id' => $project->user->id,
                        'name' => trim(($project->user->first_name ?? '') . ' ' . ($project->user->last_name ?? '')),
                        'department' => $project->user->department ? [
                            'name' => $project->user->department->name,
                        ] : null,
                    ] : null,

                    'preferred_advisers' => $project->preferredAdvisers
                        ->map(function ($adviser) {
                            $decision = $adviser->pivot?->decision
                                ?? $adviser->pivot?->adviser_status
                                ?? $adviser->pivot?->status
                                ?? 'pending';

                            return [
                                'id' => $adviser->id,
                                'name' => trim(($adviser->first_name ?? '') . ' ' . ($adviser->last_name ?? '')),
                                'preference_order' => $adviser->pivot?->preference_order,
                                'decision' => strtolower((string) $decision),
                                'is_shared' => (bool) ($adviser->pivot?->is_shared ?? false),
                                'shared_at' => $adviser->pivot?->shared_at,
                                'department' => $adviser->department ? [
                                    'name' => $adviser->department->name,
                                ] : null,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return Inertia::render('focalperson/Proposals', [
            'projects' => $mappedProjects,
        ]);
    }

    public function shareProposalToAdviser(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAccess($project);

        $validated = $request->validate([
            'adviser_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $adviserId = (int) $validated['adviser_id'];

        $project->loadMissing(['user', 'preferredAdvisers']);

        $adviser = $project->preferredAdvisers
            ->first(fn ($preferredAdviser) => (int) $preferredAdviser->id === $adviserId);

        if (! $adviser) {
            return back()->withErrors([
                'adviser_id' => 'Selected adviser is not included in this student preferred advisers.',
            ]);
        }

        DB::table('project_preferred_advisers')
            ->where('project_id', $project->id)
            ->where('adviser_id', $adviserId)
            ->update([
                'is_shared' => true,
                'shared_at' => now(),
                'updated_at' => now(),
            ]);

        $studentName = $project->user
            ? trim(($project->user->first_name ?? '') . ' ' . ($project->user->last_name ?? ''))
            : 'A student';

        NotificationService::send(
            $adviserId,
            'Proposal Shared to You',
            $studentName . ' shared their full proposal with you. Please review the submitted proposal topics and uploaded files.',
            'proposal_shared',
            $project->id,
            'project'
        );

        return back()->with('success', 'Proposal shared to preferred adviser successfully.');
    }

    public function selectTitle(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAccess($project);

        $validated = $request->validate([
            'approved_proposal_index' => ['required', 'integer', 'min:0', 'max:2'],
        ]);

        $proposalTitles = $this->toArrayValue($project->proposal_titles);
        $selectedIndex = (int) $validated['approved_proposal_index'];

        if (! isset($proposalTitles[$selectedIndex]) || blank($proposalTitles[$selectedIndex])) {
            return back()->withErrors([
                'approved_proposal_index' => 'Invalid proposal title selected.',
            ]);
        }

        DB::transaction(function () use ($project, $proposalTitles, $selectedIndex) {
            $project->update([
                'approved_proposal_index' => $selectedIndex,
                'title' => $proposalTitles[$selectedIndex],
                'status' => 'Ongoing',
                'is_resubmitted' => false,
                'resubmitted_at' => null,
            ]);
        });

        $project->load(['user', 'researchers']);

        $studentIds = collect([$project->user_id])
            ->merge($project->researchers->pluck('id'))
            ->filter()
            ->unique();

        foreach ($studentIds as $studentId) {
            NotificationService::send(
                $studentId,
                'Proposal Title Approved',
                'Your proposal title "' . $proposalTitles[$selectedIndex] . '" has been selected.',
                'proposal_title_approved',
                $project->id,
                'project'
            );
        }

        return back()->with('success', 'Preferred proposal title selected successfully.');
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

    private function authorizeAccess(Project $project): void
    {
        $authUser = Auth::user();

        $project->loadMissing(['user.department']);

        abort_if(
            (int) $project->user?->department_id !== (int) $authUser->department_id,
            403,
            'Unauthorized action.'
        );

        $allowedProjectType = $this->getAllowedProjectTypeByDepartment($authUser?->department?->name);

        if ($allowedProjectType && strcasecmp((string) $project->project_type, $allowedProjectType) !== 0) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function getAllowedProjectTypeByDepartment(?string $departmentName): ?string
    {
        $normalized = strtolower(trim((string) $departmentName));

        return match ($normalized) {
            'information technology', 'it' => 'Capstone',
            'computer science', 'cs' => 'Thesis',
            default => null,
        };
    }
}