<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        return Notification::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'created_at' => $notification->created_at,
                    'status' => $notification->status,
                    'type' => $notification->type,
                    'reference_id' => $notification->reference_id,
                    'reference_type' => $notification->reference_type,
                    'modal_title' => $this->resolveModalTitle($notification),
                    'modal_message' => $this->resolveModalMessage($notification),
                    'action_label' => $this->resolveActionLabel($notification),
                    'action_url' => $this->resolveActionUrl($notification),
                ];
            });
    }

    public function markAsRead($id)
    {
        $user = Auth::user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->update([
            'status' => 'READ',
        ]);

        return back();
    }

    public function markAllAsRead()
    {
        $user = Auth::user();

        Notification::where('user_id', $user->id)
            ->where('status', 'UNREAD')
            ->update([
                'status' => 'READ',
            ]);

        return back();
    }

    public function respondToSchedule(Request $request, $id)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'action' => ['required', 'in:confirm,reschedule'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($notification->type !== 'schedule') {
            throw ValidationException::withMessages([
                'notification' => 'This notification is not a schedule notification.',
            ]);
        }

        if (empty($notification->reference_id) || $notification->reference_type !== 'schedule') {
            throw ValidationException::withMessages([
                'notification' => 'Schedule details are missing.',
            ]);
        }

        $schedule = Schedule::with('project')->find($notification->reference_id);

        if (! $schedule) {
            throw ValidationException::withMessages([
                'notification' => 'Schedule not found.',
            ]);
        }

        $projectTitle = optional($schedule->project)->title ?? 'this project';
        $projectId = optional($schedule->project)->id;

        $actorName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $actorName = $actorName !== '' ? $actorName : 'A user';

        DB::transaction(function () use ($validated, $notification, $schedule, $user, $actorName, $projectTitle, $projectId) {
            if ($validated['action'] === 'confirm') {
                $notification->update([
                    'status' => 'READ',
                ]);

                $schedule->update([
                    'status' => 'confirmed',
                ]);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Schedule Confirmed',
                    'message' => 'You confirmed the defense schedule for "' . $projectTitle . '".',
                    'type' => 'schedule_response',
                    'reference_id' => $projectId,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);

                if (! empty($schedule->created_by) && (int) $schedule->created_by !== (int) $user->id) {
                    Notification::create([
                        'user_id' => $schedule->created_by,
                        'title' => 'Schedule Confirmed',
                        'message' => $actorName . ' confirmed the defense schedule for "' . $projectTitle . '".',
                        'type' => 'schedule_response',
                        'reference_id' => $projectId,
                        'reference_type' => 'project',
                        'status' => 'UNREAD',
                    ]);
                }
            }

            if ($validated['action'] === 'reschedule') {
                $reason = trim($validated['reason'] ?? '');

                if ($reason === '') {
                    throw ValidationException::withMessages([
                        'reason' => 'Reason is required when requesting reschedule.',
                    ]);
                }

                $notification->update([
                    'status' => 'READ',
                ]);

                $schedule->update([
                    'status' => 'reschedule_requested',
                ]);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Reschedule Requested',
                    'message' => 'You requested a reschedule for "' . $projectTitle . '". Reason: ' . $reason,
                    'type' => 'schedule_response',
                    'reference_id' => $projectId,
                    'reference_type' => 'project',
                    'status' => 'UNREAD',
                ]);

                if (! empty($schedule->created_by) && (int) $schedule->created_by !== (int) $user->id) {
                    Notification::create([
                        'user_id' => $schedule->created_by,
                        'title' => 'Reschedule Request Received',
                        'message' => $actorName . ' requested to reschedule "' . $projectTitle . '". Reason: ' . $reason,
                        'type' => 'schedule_response',
                        'reference_id' => $projectId,
                        'reference_type' => 'project',
                        'status' => 'UNREAD',
                    ]);
                }
            }
        });

        return back()->with('success', 'Schedule response submitted successfully.');
    }

    private function resolveModalTitle(Notification $notification): string
    {
        return match ($notification->type) {
            'focal_assignment' => 'Focal Person Assignment',
            'schedule' => 'Defense Schedule',
            'schedule_response' => 'Schedule Update',
            default => $notification->title ?? 'Notification',
        };
    }

    private function resolveModalMessage(Notification $notification): string
    {
        return match ($notification->type) {
            'focal_assignment' => 'You have been assigned as the Focal Person of your department.',
            default => $notification->message ?? 'You have a new notification.',
        };
    }

    private function resolveActionLabel(Notification $notification): ?string
    {
        return match ($notification->type) {
            'focal_assignment' => 'Go to Focal Person Page',
            'schedule', 'schedule_response' => 'Go to Defense Schedules',
            default => null,
        };
    }

    private function resolveActionUrl(Notification $notification): ?string
    {
        return match ($notification->type) {
            'focal_assignment' => route('focalperson.projects'),
            'schedule', 'schedule_response' => route('faculty.defense-schedules.index'),
            default => null,
        };
    }
}