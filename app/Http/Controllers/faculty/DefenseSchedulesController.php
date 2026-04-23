<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DefenseSchedulesController extends Controller
{
    public function index(): Response
    {
        $facultyId = Auth::id();

        $projects = $this->getFacultyProjects($facultyId);

        $schedules = $projects
            ->map(fn (Project $project) => $this->formatSchedule($project, $facultyId))
            ->sortBy([
                ['has_schedule', 'desc'],
                ['defense_date', 'asc'],
                ['defense_time', 'asc'],
            ])
            ->values();

        return Inertia::render('Faculty/AssignedSchedules', [
            'schedules' => $schedules,
        ]);
    }

    private function getFacultyProjects(int $facultyId)
    {
        return Project::with([
                'student.department',
                'department',
                'panelists.department',
                'adviser.department',
                'schedule',
            ])
            ->where(function ($query) use ($facultyId) {
                $query->where('adviser_id', $facultyId)
                    ->orWhereHas('panelists', function ($q) use ($facultyId) {
                        $q->where('users.id', $facultyId);
                    });
            })
            ->latest()
            ->get();
    }

    private function formatSchedule(Project $project, int $facultyId): array
    {
        $schedule = $project->schedule;

        $isAdviser = (int) $project->adviser_id === (int) $facultyId;
        $isPanelist = $project->panelists->contains(
            fn ($panelist) => (int) $panelist->id === (int) $facultyId
        );

        return [
            'id' => $schedule?->id,
            'project_id' => $project->id,
            'project_title' => $project->title ?? 'Untitled Project',
            'created_by' => $project->student?->name ?? 'Unknown',
            'project_type' => $project->project_type ?? 'Defense Schedule',
            'description' => $project->description ?? 'No description available.',
            'department' => $project->department?->name ?? 'N/A',
            'defense_date' => $schedule?->defense_date
                ? Carbon::parse($schedule->defense_date)->format('Y-m-d')
                : null,
            'defense_time' => $schedule?->defense_time,
            'venue' => $schedule?->venue,

            // IMPORTANT: ito ang actual values galing database
            'status' => $schedule?->status,
            'is_confirmed' => (int) ($schedule?->is_confirmed ?? 0),
            'reschedule_requested' => (int) ($schedule?->reschedule_requested ?? 0),

            'has_schedule' => (bool) $schedule,
            'panel_members' => $this->formatPanelists($project),
            'role' => $this->resolveRole($isAdviser, $isPanelist),
        ];
    }

    private function formatPanelists(Project $project): array
    {
        return $project->panelists->map(function ($panelist) {
            return [
                'name' => $this->resolvePanelistName($panelist),
                'department' => $panelist->department?->name ?? 'N/A',
            ];
        })->values()->all();
    }

    private function resolvePanelistName($panelist): string
    {
        if (! empty($panelist->name)) {
            return $panelist->name;
        }

        $fullName = trim(($panelist->first_name ?? '') . ' ' . ($panelist->last_name ?? ''));

        return $fullName ?: 'N/A';
    }

    private function resolveRole(bool $isAdviser, bool $isPanelist): string
    {
        if ($isAdviser && $isPanelist) {
            return 'Adviser / Panelist';
        }

        if ($isAdviser) {
            return 'Adviser';
        }

        if ($isPanelist) {
            return 'Panelist';
        }

        return 'Faculty';
    }
}