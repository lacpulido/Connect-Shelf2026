<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSoftDeleteStudents extends Command
{
    protected $signature = 'app:auto-soft-delete-students';
    protected $description = 'Deactivate students 8 months after their last completed project';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subMonths(8);

        $users = User::query()
            ->where('user_type', 2) // 2 = student
            ->whereNull('deleted_at') // not soft deleted
            ->where('is_active', 1)
            ->where('status', 'Active')
            // must have at least one completed project older than cutoff
            ->whereHas('projects', function ($query) use ($cutoff) {
                $query->where('status', 'Completed')
                    ->whereNotNull('completed_at')
                    ->where('completed_at', '<=', $cutoff);
            })
            // must NOT have any ongoing project
            ->whereDoesntHave('projects', function ($query) {
                $query->where('status', 'Ongoing');
            })
            ->get();

        $count = 0;

        foreach ($users as $user) {
            $latestCompletedProject = $user->projects()
                ->where('status', 'Completed')
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first();

            if (! $latestCompletedProject) {
                continue;
            }

            if (Carbon::parse($latestCompletedProject->completed_at)->lte($cutoff)) {
                $user->update([
                    'is_active' => 0,
                    'status' => 'Inactive',
                    'deactivated_at' => now(),
                ]);

                $count++;
            }
        }

        $this->info("{$count} student account(s) deactivated.");

        return Command::SUCCESS;
    }
}