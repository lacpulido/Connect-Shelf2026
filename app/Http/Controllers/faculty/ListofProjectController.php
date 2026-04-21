<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ListofProjectController extends Controller
{
    // ─── Shared eager-load relationships ────────────────────────────────────
    private array $projectRelations = [
        'student:id,first_name,last_name,department_id',
        'student.department:id,name',
        'schedule:id,project_id,defense_date,defense_time,venue,status',
        'panelists:id,first_name,last_name',
        'adviser:id,first_name,last_name',
        'researchers:id,first_name,last_name,email',
        'department:id,name',
    ];

    // ─── Index ───────────────────────────────────────────────────────────────
    public function index(): Response
    {
        $projects = Project::with($this->projectRelations)
            ->where('adviser_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn($project) => $this->formatProject($project))
            ->values();

        return Inertia::render('Faculty/Projects', [
            'projects'     => $projects,
            'has_projects' => $projects->isNotEmpty(),
        ]);
    }

    // ─── Show ────────────────────────────────────────────────────────────────
    public function show(Project $project): Response
    {
        // ✅ Simple ownership check — replace with a Policy if app grows
        abort_if((int) $project->adviser_id !== (int) Auth::id(), 403);

        $project->load([
            ...$this->projectRelations,
            'documents.comments.adviser',
        ]);

        return Inertia::render('Faculty/ViewProject', [
            'project'   => $this->formatProject($project),
            'documents' => $this->formatDocuments($project),
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Shared project shape for both index and show.
     */
    private function formatProject(Project $project): array
    {
        return [
            'id'            => $project->id,
            'slug'          => $project->slug,
            'title'         => $project->title,
            'description'   => $project->description,
            'project_type'  => $project->project_type,
            'semester'      => $project->semester,
            'academic_year' => $project->academic_year,
            'status'        => $project->status,
            'created_at'    => $project->created_at?->toDateTimeString(),
            'student'     => $this->formatStudent($project),
            'adviser'     => $this->formatAdviser($project),
            'panelists'   => $this->formatPanelists($project),
            'researchers' => $this->formatResearchers($project),
            'department'  => $project->department?->name ?? 'N/A',
            'schedule'    => $this->formatSchedule($project),
        ];
    }
    private function formatStudent(Project $project): array
    {
        $student = $project->student;

        return [
            'first_name' => $student?->first_name,
            'last_name'  => $student?->last_name,
            'name'       => $student?->full_name ?? 'N/A',   // ← Model accessor
            'department' => $student?->department?->name ?? 'N/A',
        ];
    }
    private function formatAdviser(Project $project): ?array
    {
        $adviser = $project->adviser;

        if (!$adviser) return null;

        return [
            'id'         => $adviser->id,
            'first_name' => $adviser->first_name,
            'last_name'  => $adviser->last_name,
            'name'       => $adviser->full_name,             // ← Model accessor
        ];
    }
    private function formatPanelists(Project $project): array
    {
        return $project->panelists->map(fn($panelist) => [
            'id'         => $panelist->id,
            'first_name' => $panelist->first_name,
            'last_name'  => $panelist->last_name,
            'name'       => $panelist->full_name,            // ← Model accessor
        ])->values()->all();
    }

    private function formatResearchers(Project $project): array
    {
        return $project->researchers->map(fn($researcher) => [
            'id'         => $researcher->id,
            'first_name' => $researcher->first_name,
            'last_name'  => $researcher->last_name,
            'name'       => $researcher->full_name,          // ← Model accessor
            'email'      => $researcher->email,
        ])->values()->all();
    }

    private function formatSchedule(Project $project): ?array
    {
        $schedule = $project->schedule;

        if (!$schedule) return null;

        return [
            'id'           => $schedule->id,
            'defense_date' => $schedule->defense_date,
            'defense_time' => $schedule->defense_time,
            'venue'        => $schedule->venue,
            'status'       => $schedule->status,
        ];
    }

    /**
     * Groups documents by parent and formats with comments.
     */
    private function formatDocuments(Project $project)
    {
        return $project->documents
            ->sortByDesc('version')
            ->groupBy(fn($doc) => $doc->parent_id ?? $doc->id)
            ->map(fn($group) => $group->map(fn($doc) => [
                'id'         => $doc->id,
                'title'      => $doc->title,
                'slug'       => $doc->slug,
                'filename'   => $doc->original_name ?? $doc->filename,
                'status'     => $doc->status,
                'version'    => $doc->version,
                'is_current' => $doc->is_current,
                'created_at' => $doc->created_at,
                'comments'   => $doc->comments->map(fn($comment) => [
                    'id'       => $comment->id,
                    'comment'  => $comment->comment,
                    'decision' => $comment->decision,
                    'adviser'  => [
                        'first_name' => $comment->adviser?->first_name,
                        'last_name'  => $comment->adviser?->last_name,
                    ],
                ])->values(),
            ])->values())
            ->values();
    }
}