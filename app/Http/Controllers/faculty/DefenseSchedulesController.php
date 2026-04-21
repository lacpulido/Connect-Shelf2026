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
            ->map(fn($project) => $this->formatSchedule($project, $facultyId))
            ->filter()
            ->sortBy([
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
                    ->orWhereHas('panelists', fn($q) => $q->where('users.id', $facultyId));
            })
            ->get();
    }
    private function formatSchedule(Project $project, int $facultyId): ?array
    {
        $schedule   = $project->schedule;
        $isAdviser  = (int) $project->adviser_id === $facultyId;
        $isPanelist = $project->panelists->contains(fn($p) => (int) $p->id === $facultyId);
        $isConfirmed = $this->isScheduleConfirmed($schedule);
        if ($isPanelist && !$isAdviser && !$isConfirmed) {
            return null;
        }
        return [
            'id'                   => $schedule?->id,
            'project_id'           => $project->id,
            'project_title'        => $project->title ?? 'Untitled Project',
            'created_by'           => $project->student?->name ?? 'Unknown',
            'project_type'         => $project->project_type ?? 'Defense Schedule',
            'description'          => $project->description ?? 'No description available.',
            'department'           => $project->department?->name ?? 'N/A',
            'defense_date'         => $schedule?->defense_date
                                        ? Carbon::parse($schedule->defense_date)->format('Y-m-d')
                                        : null,
            'defense_time'         => $schedule?->defense_time,
            'venue'                => $schedule?->venue,
            'status'               => $schedule?->status ?? 'pending_schedule',
            'is_confirmed'         => $isConfirmed,
            'reschedule_requested' => (bool) $schedule?->reschedule_requested,
            'has_schedule'         => (bool) $schedule,
            'panel_members'        => $this->formatPanelists($project),
            'role'                 => $isAdviser ? 'Adviser' : 'Panelist',
        ];
    }
    private function isScheduleConfirmed($schedule): bool
    {
        if (!$schedule) {
            return false;
        }

        return $schedule->status === 'confirmed' || (bool) $schedule->is_confirmed;
    }
    private function formatPanelists(Project $project): array
    {
        return $project->panelists->map(function ($panelist) {
            return [
                'name'       => $this->resolvePanelistName($panelist),
                'department' => $panelist->department?->name ?? 'N/A',
            ];
        })->values()->all();
    }
    private function resolvePanelistName($panelist): string
    {
        if (!empty($panelist->name)) {
            return $panelist->name;
        }

        $fullName = trim(($panelist->first_name ?? '') . ' ' . ($panelist->last_name ?? ''));
        return $fullName ?: 'N/A';
    }
}