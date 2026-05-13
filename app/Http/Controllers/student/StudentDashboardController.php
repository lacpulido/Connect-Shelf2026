<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user) {
            return Inertia::render('Student/Dashboard', [
                'project' => null,
                'schedule' => null,
                'activities' => [],
                'statusSummary' => [
                    'submitted' => 0,
                    'under_review' => 0,
                    'needs_revision' => 0,
                    'completed' => 0,
                ],
                'hasAnyProject' => false,
                'canCreateProject' => false,
                'showWelcomeBanner' => false,
                'hideProposalBox' => false,
                'allAdvisersDeclined' => false,
            ]);
        }

        $userId = $user->id;

        $project = Project::query()
            ->with(['adviser', 'preferredAdvisers', 'researchers'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->latest('id')
            ->first();

        $proposalTitles = [];
        $selectedAdvisers = collect();
        $allAdvisersDeclined = false;

        if ($project) {
            $titles = $project->proposal_titles;

            if (is_string($titles)) {
                $titles = json_decode($titles, true) ?: [];
            }

            if (! is_array($titles)) {
                $titles = [];
            }

            foreach ($titles as $index => $title) {
                $remarks = 'Pending faculty decision';

                if ($project->approved_proposal_index !== null) {
                    $remarks = (int) $project->approved_proposal_index === (int) $index
                        ? 'Approved / Preferred Title'
                        : 'Not Selected';
                }

                $proposalTitles[] = [
                    'index' => $index,
                    'title' => $title,
                    'remarks' => $remarks,
                    'is_approved' => $project->approved_proposal_index !== null &&
                        (int) $project->approved_proposal_index === (int) $index,
                ];
            }

            $selectedAdvisers = $project->preferredAdvisers
                ->sortBy(fn ($adviser) => $adviser->pivot?->preference_order ?? 999)
                ->map(function ($adviser) {
                    return [
                        'id' => $adviser->id,
                        'rank' => $adviser->pivot?->preference_order,
                        'status' => $adviser->pivot?->status ?? 'pending',
                        'is_shared' => (bool) ($adviser->pivot?->is_shared ?? false),
                        'shared_at' => $adviser->pivot?->shared_at
                            ? $adviser->pivot->shared_at
                            : null,
                        'name' => trim(($adviser->first_name ?? '') . ' ' . ($adviser->last_name ?? '')),
                    ];
                })
                ->values();

            $allAdvisersDeclined =
                $selectedAdvisers->count() > 0 &&
                $selectedAdvisers->every(fn ($adviser) => strtolower($adviser['status']) === 'declined');
        }

        $isApproved = false;
        $showWelcomeBanner = false;
        $hideProposalBox = false;

        if ($project) {
            $acceptedAdviser = $selectedAdvisers->firstWhere('status', 'accepted');
            $status = strtolower((string) ($project->status ?? ''));

            $hasAdviser = $project->adviser_id !== null || $acceptedAdviser;

            $hasApprovedTopic =
                $project->approved_proposal_index !== null ||
                in_array($status, ['approved', 'accepted', 'ongoing', 'completed'], true) ||
                $project->completed_at !== null;

            $isApproved = $hasAdviser && $hasApprovedTopic;

            $hideProposalBox = $isApproved && ! $allAdvisersDeclined;

            $sessionKey = 'student_dashboard_seen_project_' . $project->id;

            $showWelcomeBanner = $isApproved && ! session()->has($sessionKey);

            if ($isApproved) {
                session()->put($sessionKey, true);
            }
        }

        $activities = $this->getRecentActivities($project, $selectedAdvisers);

        return Inertia::render('Student/Dashboard', [
            'project' => $project ? [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'status' => $project->status ?? 'Topic Proposal',
                'project_type' => $project->project_type ?? 'N/A',
                'academic_year' => $project->academic_year ?? 'N/A',
                'semester' => $project->semester ?? 'N/A',
                'proposal_file' => $project->proposal_file,
                'proposal_original_name' => $project->proposal_original_name,
                'approved_proposal_index' => $project->approved_proposal_index,
                'adviser_id' => $project->adviser_id,

                'adviser' => [
                    'name' => $project->adviser
                        ? trim(($project->adviser->first_name ?? '') . ' ' . ($project->adviser->last_name ?? ''))
                        : 'Not Assigned',
                ],

                'selected_advisers' => $selectedAdvisers->map(function ($adviser) {
                    return [
                        ...$adviser,
                        'shared_at' => $adviser['shared_at']
                            ? date('M d, Y h:i A', strtotime($adviser['shared_at']))
                            : null,
                    ];
                })->values(),

                'proposal_titles' => $proposalTitles,

                'updated_at' => $project->updated_at
                    ? $project->updated_at->format('M d, Y')
                    : null,

                'completed_at' => $project->completed_at
                    ? $project->completed_at->format('Y-m-d')
                    : null,
            ] : null,

            'schedule' => null,
            'activities' => $activities,

            'statusSummary' => [
                'submitted' => $activities->where('type', 'submission')->count(),
                'under_review' => $activities->where('type', 'comment')->count(),
                'needs_revision' => $activities->where('type', 'revision')->count(),
                'completed' => $activities->where('type', 'approved')->count(),
            ],

            'hasAnyProject' => (bool) $project,
            'canCreateProject' => ! $project || $allAdvisersDeclined,
            'showWelcomeBanner' => $showWelcomeBanner,
            'hideProposalBox' => $hideProposalBox,
            'allAdvisersDeclined' => $allAdvisersDeclined,
        ]);
    }

    private function getRecentActivities($project, $selectedAdvisers)
    {
        $activities = collect();

        if (! $project) {
            return $activities;
        }

        if ($project->created_at) {
            $activities->push([
                'id' => 'project-created-' . $project->id,
                'type' => 'submission',
                'title' => 'Project submitted',
                'description' => 'Your project proposal was submitted successfully.',
                'created_at' => $project->created_at->toISOString(),
                'project_title' => $project->title,
            ]);
        }

        if ($project->updated_at) {
            $activities->push([
                'id' => 'project-updated-' . $project->id,
                'type' => $this->normalizeActivityType($project->status),
                'title' => 'Project updated',
                'description' => 'Your project record was recently updated.',
                'created_at' => $project->updated_at->toISOString(),
                'project_title' => $project->title,
            ]);
        }

        if ($project->approved_proposal_index !== null) {
            $activities->push([
                'id' => 'project-approved-title-' . $project->id,
                'type' => 'approved',
                'title' => 'Topic approved',
                'description' => 'One of your proposed research titles has been approved.',
                'created_at' => $project->updated_at
                    ? $project->updated_at->toISOString()
                    : now()->toISOString(),
                'project_title' => $project->title,
            ]);
        }

        if ($project->completed_at) {
            $activities->push([
                'id' => 'project-completed-' . $project->id,
                'type' => 'approved',
                'title' => 'Project completed',
                'description' => 'Your research project has been marked as completed.',
                'created_at' => $project->completed_at->toISOString(),
                'project_title' => $project->title,
            ]);
        }

        foreach ($selectedAdvisers as $adviser) {
            $status = strtolower((string) ($adviser['status'] ?? 'pending'));

            if ($status === 'pending') {
                continue;
            }

            $activities->push([
                'id' => 'adviser-' . $adviser['id'] . '-' . $status,
                'type' => $this->normalizeActivityType($status),
                'title' => 'Adviser decision updated',
                'description' => $adviser['name'] . ' marked your adviser request as ' . ucfirst($status) . '.',
                'created_at' => $adviser['shared_at']
                    ? date('c', strtotime($adviser['shared_at']))
                    : optional($project->updated_at)->toISOString(),
                'project_title' => $project->title,
            ]);
        }

        return $activities
            ->filter(fn ($activity) => ! empty($activity['created_at']))
            ->sortByDesc('created_at')
            ->values()
            ->take(5);
    }

    private function normalizeActivityType(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'submitted', 'submission', 'proposal' => 'submission',
            'under_review', 'review', 'pending' => 'comment',
            'approved', 'accepted', 'completed', 'ongoing' => 'approved',
            'revision', 'needs_revision', 'declined' => 'revision',
            default => 'submission',
        };
    }

    public function show(Project $project)
    {
        $this->authorizeStudentProject($project);

        $project->load(['adviser']);

        return Inertia::render('Student/Show', [
            'project' => $this->formatProject($project),
        ]);
    }

    public function edit(Project $project)
    {
        $this->authorizeStudentProject($project);

        $project->load(['adviser', 'researchers']);

        return Inertia::render('Student/Edit', [
            'project' => [
                'id' => $project->id,
                'slug' => $project->slug,
                'title' => $project->title,
                'project_type' => $project->project_type,
                'academic_year' => $project->academic_year,
                'semester' => $project->semester,

                'adviser' => [
                    'name' => $project->adviser
                        ? trim(($project->adviser->first_name ?? '') . ' ' . ($project->adviser->last_name ?? ''))
                        : 'Not Assigned',
                ],

                'researchers' => $project->researchers->map(function ($r) {
                    return trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                })->values(),
            ],
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeStudentProject($project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $project->update([
            'title' => trim($validated['title']),
        ]);

        return back()->with('success', 'Project title updated successfully.');
    }

    public function resubmitTopics(Request $request, Project $project)
    {
        $this->authorizeStudentProject($project);

        $project->load('preferredAdvisers');

        $allAdvisersDeclined =
            $project->preferredAdvisers->count() > 0 &&
            $project->preferredAdvisers->every(function ($adviser) {
                return strtolower((string) ($adviser->pivot?->status ?? 'pending')) === 'declined';
            });

        abort_unless($allAdvisersDeclined, 403);

        $validated = $request->validate([
            'proposal_titles' => ['required', 'array', 'size:3'],
            'proposal_titles.*' => ['required', 'string', 'max:250', 'distinct'],

            'proposal_files' => ['required', 'array', 'size:3'],
            'proposal_files.*' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $storedPaths = [];
        $originalNames = [];

        foreach ($request->file('proposal_files') as $index => $file) {
            $path = $file->store('proposal-files', 'public');

            $storedPaths[$index] = $path;
            $originalNames[$index] = $file->getClientOriginalName();
        }

        $project->update([
            'title' => trim($validated['proposal_titles'][0]),
            'proposal_titles' => array_map(fn ($title) => trim($title), $validated['proposal_titles']),
            'proposal_files' => $storedPaths,
            'proposal_original_names' => $originalNames,

            'proposal_file' => $storedPaths[0] ?? null,
            'proposal_original_name' => $originalNames[0] ?? null,

            'approved_proposal_index' => null,
            'adviser_id' => null,
            'status' => 'Proposal',

            'is_resubmitted' => true,
            'resubmitted_at' => now(),
        ]);

        foreach ($project->preferredAdvisers as $adviser) {
            $project->preferredAdvisers()->updateExistingPivot($adviser->id, [
                'status' => 'pending',
                'is_shared' => false,
                'shared_at' => null,
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'New topic proposals submitted successfully.');
    }

    private function authorizeStudentProject(Project $project): void
    {
        $userId = Auth::id();

        $allowed = (int) $project->user_id === (int) $userId ||
            $project->researchers()
                ->where('users.id', $userId)
                ->exists();

        abort_unless($allowed, 403);
    }

    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'title' => $project->title,
            'abstract' => $project->abstract,
            'status' => $project->status ?? 'In Progress',
            'project_type' => $project->project_type ?? 'N/A',
            'academic_year' => $project->academic_year ?? 'N/A',
            'semester' => $project->semester ?? 'N/A',
            'research_area' => $project->research_area ?? 'Education',
            'keywords' => $project->keywords ?? 'N/A',

            'completed_at' => $project->completed_at
                ? $project->completed_at->format('Y-m-d')
                : null,

            'formatted_completed_at' => $project->completed_at
                ? $project->completed_at->format('M d, Y')
                : null,

            'updated_at' => $project->updated_at
                ? $project->updated_at->format('M d, Y')
                : null,

            'adviser' => [
                'name' => $project->adviser
                    ? trim(($project->adviser->first_name ?? '') . ' ' . ($project->adviser->last_name ?? ''))
                    : 'Not Assigned',
            ],
        ];
    }
}