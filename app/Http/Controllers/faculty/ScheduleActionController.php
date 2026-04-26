<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleActionController extends Controller
{
    private const ALLOWED_TIME_SLOTS = [
        '8:00 AM - 11:00 AM',
        '11:00 AM - 2:00 PM',
        '2:00 PM - 5:00 PM',
    ];

    public function confirm(int $schedule): RedirectResponse
    {
        $schedule = $this->findAuthorizedSchedule($schedule);

        DB::transaction(function () use ($schedule) {
            $schedule->update([
                'is_confirmed' => true,
                'status' => 'confirmed',
                'reschedule_requested' => false,
                'requested_defense_date' => null,
                'requested_defense_time' => null,
                'reschedule_reason' => null,
            ]);

            $actorName = $this->facultyName();

            $scheduleText = $this->formatScheduleText(
                $schedule->defense_date,
                $schedule->defense_time,
                $schedule->venue
            );

            $this->sendProjectNotifications(
                schedule: $schedule,
                title: 'Schedule Confirmed by ' . $actorName,
                message: $actorName . ' confirmed the defense schedule for project "' .
                    ($schedule->project->title ?? 'Untitled') . '" on ' . $scheduleText . '.',
                type: 'schedule_confirmation',
            );
        });

        return back()->with('success', 'Schedule confirmed successfully.');
    }

    public function requestReschedule(Request $request, int $schedule): RedirectResponse
    {
        $schedule = $this->findAuthorizedSchedule($schedule);

        $validated = $request->validate([
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'preferred_time' => ['required', Rule::in(self::ALLOWED_TIME_SLOTS)],
        ]);

        DB::transaction(function () use ($schedule, $validated) {
            $schedule->update([
                'requested_defense_date' => $validated['preferred_date'],
                'requested_defense_time' => $validated['preferred_time'],
                'reschedule_requested' => true,
                'is_confirmed' => false,
                'status' => 'reschedule_requested',
                'reschedule_reason' => null,
            ]);

            $actorName = $this->facultyName();

            $requestedDate = Carbon::parse($validated['preferred_date'])->format('F d, Y');

            $requestedScheduleText = $this->formatScheduleText(
                $validated['preferred_date'],
                $validated['preferred_time'],
                $schedule->venue
            );

            $projectTitle = $schedule->project->title ?? 'Untitled';

            $this->sendFocalPersonNotifications(
                schedule: $schedule,
                title: 'Reschedule Date Requested',
                message: $actorName . ' requested to reschedule the defense for project "' .
                    $projectTitle . '". Requested date: ' . $requestedDate .
                    '. Requested time: ' . $validated['preferred_time'] .
                    '. Requested schedule: ' . $requestedScheduleText . '.',
                type: 'reschedule_request',
            );

            $this->sendProjectNotifications(
                schedule: $schedule,
                title: 'Reschedule Requested by ' . $actorName,
                message: $actorName . ' requested a reschedule for project "' .
                    $projectTitle . '" — preferred: ' . $requestedScheduleText . '.',
                type: 'reschedule_request',
            );
        });

        return back()->with('success', 'Reschedule request submitted successfully.');
    }

    private function findAuthorizedSchedule(int $scheduleId): Schedule
    {
        $schedule = Schedule::with([
            'project.adviser',
            'project.student',
            'project.user',
            'project.researchers',
            'project.panelists',
        ])->findOrFail($scheduleId);

        $facultyId = Auth::id();

        $allowed = $schedule->project()
            ->where(function ($query) use ($facultyId) {
                $query->where('adviser_id', $facultyId)
                    ->orWhereHas('panelists', function ($q) use ($facultyId) {
                        $q->where('users.id', $facultyId);
                    });
            })
            ->exists();

        abort_unless($allowed, 403);

        return $schedule;
    }

    private function sendFocalPersonNotifications(
        Schedule $schedule,
        string $title,
        string $message,
        string $type
    ): void {
        $focalPersonIds = User::whereHas('roles', function ($q) {
            $q->where('name', 'Focal Person');
        })->pluck('id');

        foreach ($focalPersonIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $schedule->id,
                'reference_type' => 'schedule',
                'status' => 'UNREAD',
            ]);
        }
    }

    private function sendProjectNotifications(
        Schedule $schedule,
        string $title,
        string $message,
        string $type
    ): void {
        $project = $schedule->project;

        if (!$project) {
            return;
        }

        $recipientIds = $this->buildRecipientIds($project);

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $schedule->id,
                'reference_type' => 'schedule',
                'status' => 'UNREAD',
            ]);
        }
    }

    private function buildRecipientIds(Project $project): Collection
    {
        $currentUserId = Auth::id();

        return collect()
            ->push($project->adviser_id)
            ->push($project->user_id)
            ->merge($project->researchers?->pluck('id') ?? collect())
            ->merge($project->panelists?->pluck('id') ?? collect())
            ->filter(fn ($id) => !is_null($id) && (int) $id !== (int) $currentUserId)
            ->unique()
            ->values();
    }

    private function formatScheduleText(?string $date, ?string $time, ?string $venue): string
    {
        $formattedDate = $date
            ? Carbon::parse($date)->format('F d, Y')
            : 'TBA';

        return "{$formattedDate} | " . ($time ?? 'TBA') . " | " . ($venue ?? 'TBA');
    }

    private function facultyName(): string
    {
        $user = Auth::user();

        if (!$user) {
            return 'A faculty member';
        }

        $name = trim((string) ($user->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        return $fullName !== '' ? $fullName : ($user->email ?? 'A faculty member');
    }
}