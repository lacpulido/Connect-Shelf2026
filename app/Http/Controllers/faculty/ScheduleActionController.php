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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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
                'is_confirmed'         => true,
                'status'               => 'confirmed',
                'reschedule_requested' => false,
                'reschedule_reason'    => null,
            ]);

            $actorName = $this->facultyName();

            $scheduleText = $this->formatScheduleText(
                $schedule->defense_date,
                $schedule->defense_time,
                $schedule->venue
            );

            $this->sendNotifications(
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
                'defense_date'         => $validated['preferred_date'],
                'defense_time'         => $validated['preferred_time'],
                'reschedule_requested' => true,
                'is_confirmed'         => false,
                'status'               => 'reschedule_requested',
                'reschedule_reason'    => null,
            ]);

            $actorName = $this->facultyName();

            $scheduleText = $this->formatScheduleText(
                $validated['preferred_date'],
                $validated['preferred_time'],
                $schedule->venue
            );

            $this->sendNotifications(
                schedule: $schedule,
                title: 'Reschedule Requested by ' . $actorName,
                message: $actorName . ' requested a reschedule for project "' .
                    ($schedule->project->title ?? 'Untitled') . '" — preferred: ' . $scheduleText . '.',
                type: 'reschedule_request',
            );
        });

        return back()->with('success', 'Reschedule request submitted successfully.');
    }

    /**
     * Find a schedule and abort if the faculty member is not the adviser or a panelist.
     */
    private function findAuthorizedSchedule(int $scheduleId): Schedule
    {
        $schedule = Schedule::with([
            'project.adviser',
            'project.student',
            'project.researchers',
            'project.panelists',
        ])->findOrFail($scheduleId);

        $facultyId = Auth::id();

        $allowed = $schedule->project()
            ->where(function ($query) use ($facultyId) {
                $query->where('adviser_id', $facultyId)
                    ->orWhereHas('panelists', fn ($q) => $q->where('users.id', $facultyId));
            })
            ->exists();

        abort_unless($allowed, 403);

        return $schedule;
    }

    /**
     * Build and send notifications to all relevant recipients except the acting user.
     */
    private function sendNotifications(Schedule $schedule, string $title, string $message, string $type): void
    {
        $project = $schedule->project;

        if (!$project) {
            return;
        }

        $recipientIds = $this->buildRecipientIds($project);

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id'        => $userId,
                'title'          => $title,
                'message'        => $message,
                'type'           => $type,
                'reference_id'   => $schedule->id,
                'reference_type' => 'schedule',
                'status'         => 'UNREAD',
            ]);
        }
    }

    /**
     * Collect all unique recipient IDs for a project notification,
     * excluding the currently authenticated user to avoid self-notification.
     */
    private function buildRecipientIds(Project $project): Collection
    {
        $currentUserId = Auth::id();

        $focalPersonIds = User::whereHas('roles', fn ($q) => $q->where('name', 'Focal Person'))
            ->pluck('id');

        return collect()
            ->merge($focalPersonIds)
            ->push($project->adviser_id)
            ->push($project->user_id)
            ->merge($project->researchers->pluck('id'))
            ->merge($project->panelists->pluck('id'))
            ->filter(fn ($id) => !is_null($id) && (int) $id !== (int) $currentUserId)
            ->unique()
            ->values();
    }

    /**
     * Format date, time, and venue into a readable string.
     */
    private function formatScheduleText(?string $date, ?string $time, ?string $venue): string
    {
        $formattedDate = $date ? Carbon::parse($date)->format('F d, Y') : 'TBA';

        return "{$formattedDate} | " . ($time ?? 'TBA') . " | " . ($venue ?? 'TBA');
    }

    /**
     * Get the display name of the authenticated faculty member.
     */
    private function facultyName(): string
    {
        $user = Auth::user();

        if (!$user) {
            return 'A faculty member';
        }

        if (!empty($user->name) && trim($user->name) !== '') {
            return trim($user->name);
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName  = trim((string) ($user->last_name ?? ''));
        $fullName  = trim($firstName . ' ' . $lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        $fNameAlt    = trim((string) ($user->fname ?? ''));
        $lNameAlt    = trim((string) ($user->lname ?? ''));
        $fullNameAlt = trim($fNameAlt . ' ' . $lNameAlt);

        if ($fullNameAlt !== '') {
            return $fullNameAlt;
        }

        if (!empty($user->email) && trim($user->email) !== '') {
            return trim($user->email);
        }

        return 'A faculty member';
    }
}