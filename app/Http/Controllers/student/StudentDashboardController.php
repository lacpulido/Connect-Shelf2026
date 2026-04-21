<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Form;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;

        $hasAnyProject = Project::withTrashed()
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->exists();

        $project = Project::withTrashed()
            ->with([
                'adviser',
                'documents',
                'panelists.department',
            ])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('researchers', function ($q) use ($userId) {
                        $q->where('users.id', $userId);
                    });
            })
            ->latest('id')
            ->first();

        $statusSummary = [
            'submitted' => 0,
            'under_review' => 0,
            'needs_revision' => 0,
            'completed' => 0,
        ];

        $activities = [];
        $schedule = null;

        if ($project) {
            $panelists = $project->panelists->map(function ($panelist) {
                return [
                    'name' => trim(($panelist->first_name ?? '') . ' ' . ($panelist->last_name ?? '')),
                    'department' => $panelist->department->name ?? 'N/A',
                ];
            })->values();

            $latestSchedule = $project->schedule()
                ->latest('id')
                ->first();

            if ($latestSchedule) {
                $isConfirmed =
                    $latestSchedule->status === 'confirmed' &&
                    (int) $latestSchedule->is_confirmed === 1;

                $schedule = [
                    'id' => $latestSchedule->id,
                    'defense_date' => $latestSchedule->defense_date,
                    'defense_time' => $latestSchedule->defense_time,
                    'venue' => $latestSchedule->venue,
                    'panelists' => $panelists,
                    'status' => $isConfirmed ? 'confirmed' : 'pending',
                    'project' => [
                        'title' => $project->title,
                    ],
                ];
            }

            $documents = $project->documents()
                ->where('is_current', 1)
                ->latest('updated_at')
                ->get();

            $statusSummary = [
                'submitted' => $documents->where('status', 'submitted')->count(),
                'under_review' => $documents->where('status', 'under_review')->count(),
                'needs_revision' => $documents->where('status', 'needs_revision')->count(),
                'completed' => $documents->where('status', 'approved')->count(),
            ];

            $activities = $documents->map(function ($document) use ($project) {
                $status = strtolower($document->status ?? 'submitted');

                $title = match ($status) {
                    'under_review' => 'Document is under review',
                    'needs_revision' => 'Revision requested by adviser',
                    'approved' => 'Document approved by adviser',
                    default => 'Document submitted',
                };

                $type = match ($status) {
                    'under_review' => 'under_review',
                    'needs_revision' => 'needs_revision',
                    'approved' => 'approved',
                    default => 'submission',
                };

                $description = $document->title;

                if (!empty($document->review_comment) && $status === 'needs_revision') {
                    $description .= ' — ' . $document->review_comment;
                }

                return [
                    'id' => 'activity-' . $document->id . '-' . $status,
                    'type' => $type,
                    'title' => $title,
                    'description' => $description,
                    'created_at' => in_array($status, ['under_review', 'needs_revision', 'approved'])
                        ? $document->updated_at
                        : $document->created_at,
                    'project_title' => $project->title,
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->take(5)
            ->toArray();
        }

        $forms = Form::where('department_id', $user->department_id)
            ->latest()
            ->get();

        return Inertia::render('Student/Dashboard', [
            'project' => $project,
            'schedule' => $schedule,
            'statusSummary' => $statusSummary,
            'activities' => $activities,
            'forms' => $forms,
            'hasAnyProject' => $hasAnyProject,
            'canCreateProject' => !$hasAnyProject,
        ]);
    }
}