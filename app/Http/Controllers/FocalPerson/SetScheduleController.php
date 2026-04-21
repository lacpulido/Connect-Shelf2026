<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SetScheduleController extends Controller
{
    public function store(Request $request)
    {
        $allowedTimeSlots = [
            '8:00 AM - 11:00 AM',
            '11:00 AM - 2:00 PM',
            '2:00 PM - 5:00 PM',
        ];

        $validated = $request->validate([
            'project_id'   => ['required', 'exists:projects,id'],
            'defense_date' => ['required', 'date', 'after_or_equal:today'],
            'defense_time' => ['required', Rule::in($allowedTimeSlots)],
            'venue'        => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $now = Carbon::now('Asia/Manila');

            $existingSchedule = Schedule::where('project_id', $validated['project_id'])->first();

            $schedule = Schedule::updateOrCreate(
                ['project_id' => $validated['project_id']],
                [
                    'created_by'           => Auth::id(),
                    'defense_date'         => $validated['defense_date'],
                    'defense_time'         => $validated['defense_time'],
                    'venue'                => trim($validated['venue']),
                    'status'               => 'pending',
                    'is_confirmed'         => false,
                    'reschedule_requested' => false,
                    'updated_at'           => $now,
                ]
            );

            $project = Project::with(['researchers', 'adviser'])
                ->findOrFail($validated['project_id']);

            $formattedDate = Carbon::parse($schedule->defense_date, 'Asia/Manila')->format('F d, Y');

            $scheduleText = $formattedDate . ' | ' .
                $schedule->defense_time . ' | ' .
                $schedule->venue;

            $notificationTitle = $existingSchedule
                ? 'Defense Schedule Updated'
                : 'Defense Schedule Set';

            $studentMessage = 'Your project "' . $project->title .
                '" has been scheduled on ' . $scheduleText .
                '. This schedule is still pending confirmation.';

            $adviserMessage = 'Project "' . $project->title .
                '" has been scheduled on ' . $scheduleText .
                '. This schedule is still pending confirmation.';

            foreach ($project->researchers as $student) {
                if (!$student || !isset($student->id)) {
                    continue;
                }

                Notification::create([
                    'user_id'        => $student->id,
                    'title'          => $notificationTitle,
                    'message'        => $studentMessage,
                    'type'           => 'schedule',
                    'reference_id'   => $schedule->id,
                    'reference_type' => 'schedule',
                    'status'         => 'UNREAD',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            if ($project->adviser) {
                Notification::create([
                    'user_id'        => $project->adviser->id,
                    'title'          => 'Project Schedule Assigned',
                    'message'        => $adviserMessage,
                    'type'           => 'schedule',
                    'reference_id'   => $schedule->id,
                    'reference_type' => 'schedule',
                    'status'         => 'UNREAD',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        });

        return back()->with('success', 'Schedule saved as pending and notifications sent.');
    }
}