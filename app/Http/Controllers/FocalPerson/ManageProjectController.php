<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Mail\PanelistAssignedMail;
use App\Models\Department;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ManageProjectController extends Controller
{
    public function manage(Request $request, Project $project): Response
    {
        $project->load([
            'panelists.department',
            'user',
            'researchers',
            'schedule',
            'adviser',
        ]);

        $faculties = User::with(['department', 'roles'])
            ->where('user_type', 1)
            ->where('id', '!=', optional($project->adviser)->id)
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('focalperson/ManageProject', [
            'project' => $project,
            'faculties' => $faculties,
            'departments' => $departments,
            'filters' => [
                'department_id' => null,
            ],
            'requestedDate' => $project->schedule?->requested_defense_date,
            'requestedTime' => $project->schedule?->requested_defense_time,
        ]);
    }

    public function storeSchedule(Request $request): RedirectResponse
    {
        $allowedTimeSlots = [
            '8:00 AM - 11:00 AM',
            '11:00 AM - 2:00 PM',
            '2:00 PM - 5:00 PM',
        ];

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'defense_date' => ['required', 'date', 'after_or_equal:today'],
            'defense_time' => ['required', Rule::in($allowedTimeSlots)],
            'venue' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $now = Carbon::now('Asia/Manila');

            $existingSchedule = Schedule::where('project_id', $validated['project_id'])->first();

            if ($existingSchedule && !$existingSchedule->reschedule_requested) {
                throw ValidationException::withMessages([
                    'schedule' => 'Schedule is already set. You can only update it when a reschedule request is made.',
                ]);
            }

            $schedule = Schedule::updateOrCreate(
                ['project_id' => $validated['project_id']],
                [
                    'created_by' => Auth::id(),
                    'defense_date' => $validated['defense_date'],
                    'defense_time' => $validated['defense_time'],
                    'venue' => trim($validated['venue']),
                    'status' => 'pending',
                    'is_confirmed' => false,
                    'reschedule_requested' => false,
                    'requested_defense_date' => null,
                    'requested_defense_time' => null,
                    'reschedule_reason' => null,
                    'updated_at' => $now,
                ]
            );

            $project = Project::with([
                'researchers',
                'adviser',
                'panelists.department',
                'schedule',
            ])->findOrFail($validated['project_id']);

            $scheduleText = $this->formatScheduleText($project);

            $notificationTitle = $existingSchedule
                ? 'Defense Schedule Updated'
                : 'Defense Schedule Set';

            $message = 'Project "' . $project->title .
                '" has been scheduled on ' . $scheduleText .
                '. This schedule is still pending confirmation.';

            foreach ($project->researchers as $student) {
                Notification::create([
                    'user_id' => $student->id,
                    'title' => $notificationTitle,
                    'message' => $message,
                    'type' => 'schedule',
                    'reference_id' => $schedule->id,
                    'reference_type' => 'schedule',
                    'status' => 'UNREAD',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($project->adviser) {
                Notification::create([
                    'user_id' => $project->adviser->id,
                    'title' => $notificationTitle,
                    'message' => $message,
                    'type' => 'schedule',
                    'reference_id' => $schedule->id,
                    'reference_type' => 'schedule',
                    'status' => 'UNREAD',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($project->panelists as $panelist) {
                Notification::create([
                    'user_id' => $panelist->id,
                    'title' => $notificationTitle,
                    'message' => $message,
                    'type' => 'schedule',
                    'reference_id' => $schedule->id,
                    'reference_type' => 'schedule',
                    'status' => 'UNREAD',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->sendPanelistEmail($project, $panelist);
            }
        });

        return back()->with('success', 'Schedule saved successfully.');
    }

    public function storePanelist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'faculty_id' => ['required', 'exists:users,id'],
        ]);

        $project = Project::with([
            'panelists',
            'user',
            'researchers',
            'schedule',
            'adviser',
        ])->findOrFail($validated['project_id']);

        $faculty = User::with(['roles', 'department'])
            ->where('user_type', 1)
            ->findOrFail($validated['faculty_id']);

        $this->validatePanelistAssignment($project, $faculty);

        DB::transaction(function () use ($project, $faculty) {
            $this->attachPanelist($project, $faculty);

            $project->load([
                'schedule',
                'panelists.department',
            ]);

            $this->sendPanelistEmail($project, $faculty);
        });

        return back()->with('success', 'Panelist assigned successfully.');
    }

    private function validatePanelistAssignment(Project $project, User $faculty): void
    {
        if ($project->panelists->contains('id', $faculty->id)) {
            $this->fail('faculty_id', 'This faculty is already assigned as a panelist.');
        }

        if ($project->panelists->count() >= 3) {
            $this->fail('faculty_id', 'Maximum of 3 panelists allowed.');
        }

        if ($project->adviser && (int) $project->adviser->id === (int) $faculty->id) {
            $this->fail('faculty_id', 'The adviser cannot also be assigned as a panelist.');
        }

        if ($faculty->roles->contains('name', 'Administrator')) {
            $this->fail('faculty_id', 'Administrators cannot be assigned as panelists.');
        }
    }

    private function attachPanelist(Project $project, User $faculty): void
    {
        $project->panelists()->syncWithoutDetaching([$faculty->id]);

        $panelRole = Role::where('name', 'Panel Member')->first();

        if ($panelRole && !$faculty->roles->contains('id', $panelRole->id)) {
            $faculty->roles()->syncWithoutDetaching([$panelRole->id]);
        }
    }

    private function sendPanelistEmail(Project $project, User $panelist): void
    {
        if (empty($panelist->email)) {
            return;
        }

        $project->loadMissing('schedule');

        $scheduleText = $this->formatScheduleText($project);

        try {
            Mail::to($panelist->email)->send(
                new PanelistAssignedMail($project, $panelist, $scheduleText)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send panelist email.', [
                'panelist_id' => $panelist->id,
                'panelist_email' => $panelist->email,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function formatScheduleText(Project $project): string
    {
        $schedule = $project->schedule;

        if (!$schedule) {
            return 'Schedule not yet assigned';
        }

        $formattedDate = Carbon::parse($schedule->defense_date)->format('F d, Y');

        return $formattedDate . ' | ' .
            $schedule->defense_time . ' | ' .
            ($schedule->venue ?: 'TBA');
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }

    public function markFirstSemesterPassed(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $project = Project::findOrFail($validated['project_id']);

            if ($project->semester !== '1st Semester') {
                throw ValidationException::withMessages([
                    'semester' => 'Only 1st Semester projects can be marked as passed.',
                ]);
            }

            $project->update([
                'semester' => '2nd Semester',
                'status' => 'Ongoing',
            ]);

            Schedule::where('project_id', $project->id)->delete();
        });

        return back()->with(
            'success',
            'Project marked as passed. Semester changed to 2nd Semester and schedule was reset.'
        );
    }
}