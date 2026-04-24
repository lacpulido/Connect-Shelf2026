<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class FacultyDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $projectIds = $this->assignedProjectIds($userId);
        $documentCounts = $this->documentStatusCounts($projectIds);
        $schedules = $this->confirmedSchedules($userId);

        return Inertia::render('Faculty/Dashboard', [
            'has_projects' => $projectIds->isNotEmpty(),
            'activities' => $this->recentActivities($projectIds),
            'submitted' => $this->statusCount($documentCounts, 'submitted'),
            'under_review' => $this->statusCount($documentCounts, 'under_review'),
            'needs_revision' => $this->statusCount($documentCounts, 'needs_revision'),
            'completed' => $this->statusCount($documentCounts, 'approved'),
            'schedule' => $schedules->first(),
            'schedules' => $schedules,
            'notifications' => $this->notifications($userId),
        ]);
    }

    private function assignedProjectIds(int $userId): Collection
    {
        return Project::query()
            ->where('adviser_id', $userId)
            ->pluck('id');
    }

    private function documentStatusCounts(Collection $projectIds): Collection
    {
        return ProjectDocument::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereIn('project_id', $projectIds)
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    private function recentActivities(Collection $projectIds): Collection
    {
        return ProjectDocument::query()
            ->with('project.student')
            ->whereIn('project_id', $projectIds)
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn ($document) => [
                'id' => (string) $document->id,
                'title' => $document->title,
                'type' => $document->status,
                'description' => $this->userName($document->project?->student, 'Student') . ' submitted ' . $document->title,
                'created_at' => optional($document->updated_at)->toDateTimeString(),
                'project_title' => $document->project?->title,
            ])
            ->values();
    }

    private function confirmedSchedules(int $userId): Collection
    {
        return Schedule::query()
            ->with([
                'project.panelists.department',
                'project.student',
            ])
            ->where(function ($query) use ($userId) {
                $query->whereHas('project', function ($projectQuery) use ($userId) {
                    $projectQuery
                        ->where('adviser_id', $userId)
                        ->orWhere('user_id', $userId);
                })
                ->orWhereHas('project.panelists', function ($panelistQuery) use ($userId) {
                    $panelistQuery->where('users.id', $userId);
                });
            })
            ->where('status', 'confirmed')
            ->where('is_confirmed', 1)
            ->latest('updated_at')
            ->get()
            ->map(fn ($schedule) => $this->formatSchedule($schedule, $userId))
            ->values();
    }

    private function formatSchedule(Schedule $schedule, int $userId): array
    {
        $project = $schedule->project;

        return [
            'id' => $schedule->id,
            'defense_date' => $schedule->defense_date,
            'defense_time' => $schedule->defense_time,
            'venue' => $schedule->venue,
            'status' => $schedule->status,
            'is_confirmed' => (int) $schedule->is_confirmed,
            'project' => [
                'title' => $project?->title,
                'student' => $this->userName($project?->student, 'N/A'),
            ],
            'panelists' => $project?->panelists
                ? $project->panelists->map(fn ($panelist) => [
                    'name' => $this->userName($panelist),
                    'department' => optional($panelist->department)->name ?? 'N/A',
                ])->values()
                : [],
            'role' => $this->scheduleRole($project, $userId),
        ];
    }

    private function scheduleRole(?Project $project, int $userId): string
    {
        if ($project?->user_id == $userId) {
            return 'Student';
        }

        if ($project?->adviser_id == $userId) {
            return 'Adviser';
        }

        return 'Panelist';
    }

    private function notifications(int $userId): Collection
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'status' => $notification->status,
                'meta' => $notification->meta,
                'created_at' => optional($notification->created_at)->toDateTimeString(),
            ])
            ->values();
    }

    private function statusCount(Collection $counts, string $status): int
    {
        return (int) ($counts[$status] ?? 0);
    }

    private function userName($user, string $fallback = 'N/A'): string
    {
        if (! $user) {
            return $fallback;
        }

        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $fallback;
    }
}
