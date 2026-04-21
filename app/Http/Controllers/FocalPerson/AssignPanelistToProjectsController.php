<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AssignPanelistToProjectsController extends Controller
{
    // ─── Public Methods ──────────────────────────────────────────────────────

    public function index(Request $request, Project $project): Response
    {
        $project->load(['panelists.department', 'user', 'schedule', 'adviser']);

        $departmentId = $request->integer('department_id') ?: null;

        $faculties = User::with(['department', 'roles'])
            ->where('user_type', 'faculty')
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return Inertia::render('FocalPerson/AssignPanelists', [
            'project'     => $project,
            'faculties'   => $faculties,
            'departments' => $departments,
            'filters'     => ['department_id' => $departmentId],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'faculty_id' => ['required', 'exists:users,id'],
        ]);

        $project = Project::with(['panelists', 'user', 'schedule', 'adviser'])
            ->findOrFail($validated['project_id']);

        $faculty = User::with(['roles', 'department'])->findOrFail($validated['faculty_id']);

        $this->validatePanelistAssignment($project, $faculty);

        DB::transaction(function () use ($project, $faculty) {
            $this->attachPanelist($project, $faculty);
            $this->notifyParties($project, $faculty);
        });

        return back()->with('success', 'Panelist assigned successfully.');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Run all business rule checks before assigning a panelist.
     */
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
    }

    /**
     * Attach the faculty as a panelist and assign the Panel Member role if needed.
     */
    private function attachPanelist(Project $project, User $faculty): void
    {
        $project->panelists()->attach($faculty->id);

        $panelRole = Role::where('name', 'Panel Member')->firstOrFail();

        // Only attach the role if they don't already have it
        if (!$faculty->roles->contains('id', $panelRole->id)) {
            $faculty->roles()->attach($panelRole->id);
        }
    }

    /**
     * Notify the assigned panelist and the project owner.
     */
    private function notifyParties(Project $project, User $faculty): void
    {
        $scheduleText = $this->formatScheduleText($project);

        // Notify the assigned faculty
        NotificationService::send(
            $faculty->id,
            'Assigned as Panelist',
            'You have been assigned as panelist for project "' . $project->title . '". ' .
            'Defense schedule: ' . $scheduleText,
            'panelist_assignment',
            $project->id,
            'project'
        );

        // Notify the project owner
        if ($project->user) {
            NotificationService::send(
                $project->user->id,
                'Panelist Assigned',
                $faculty->full_name . ' has been assigned as panelist for your project "' .
                $project->title . '".',
                'panelist_assignment',
                $project->id,
                'project'
            );
        }
    }

    /**
     * Format the defense schedule into a readable string.
     */
    private function formatScheduleText(Project $project): string
    {
        $schedule = $project->schedule;

        if (!$schedule) {
            return 'Schedule not yet assigned';
        }

        return $schedule->defense_date . ' ' .
               $schedule->defense_time . ' at ' .
               ($schedule->venue ?: 'TBA');
    }

    /**
     * Throw a validation error with a specific field and message.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}