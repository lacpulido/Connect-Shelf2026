<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSoftDeleteStudents extends Command
{
    protected $signature = 'app:auto-soft-delete-students';
    protected $description = 'Deactivate students after their last completed project for testing';

    public function handle(): int
    {
        // TEST MODE:
        // Any completed project older than 1 minute will qualify
        $cutoff = Carbon::now()->subMinute();

        $this->info('Cutoff: ' . $cutoff->toDateTimeString());

        $users = User::query()
            ->where('user_type', 2) // 2 = student
            ->whereNull('deleted_at') // not soft deleted
            ->where('is_active', 1)
            ->where('status', 'Active')
            ->whereHas('projects', function ($query) {
                $query->where('status', 'Completed')
                    ->whereNotNull('completed_at');
            })
            ->whereDoesntHave('projects', function ($query) {
                $query->where('status', 'Ongoing');
            })
            ->get();

        $this->info('Matched users before latest-project check: ' . $users->count());

        $count = 0;

        foreach ($users as $user) {
            $latestCompletedProject = $user->projects()
                ->where('status', 'Completed')
                ->whereNotNull('completed_at')
                ->latest('completed_at')
                ->first();

            if (! $latestCompletedProject) {
                $this->info("User {$user->id}: no completed project found.");
                continue;
            }

            $completedAt = Carbon::parse($latestCompletedProject->completed_at);

            $this->info("User {$user->id} latest completed_at: " . $completedAt->toDateTimeString());

            if ($completedAt->lte($cutoff)) {
                $user->update([
                    'is_active' => 0,
                    'status' => 'Inactive',
                    'deactivated_at' => now(),
                ]);

                $this->info("User {$user->id} deactivated.");
                $count++;
            } else {
                $this->info("User {$user->id} not yet eligible.");
            }
        }

        $this->info("Total deactivated: {$count}");

        return Command::SUCCESS;
    }
}