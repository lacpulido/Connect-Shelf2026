<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSoftDeleteProjects extends Command
{
    protected $signature = 'app:auto-soft-delete-projects';
    protected $description = 'Soft delete completed projects automatically';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subMinute();

        $this->info('Cutoff: ' . $cutoff->toDateTimeString());

        $projects = Project::query()
            ->where('status', 'Completed')
            ->whereNotNull('completed_at')
            ->whereNull('deleted_at')
            ->get();

        $this->info('Matched projects before cutoff check: ' . $projects->count());

        $count = 0;

        foreach ($projects as $project) {
            $completedAt = Carbon::parse($project->completed_at);

            $this->info("Project {$project->id} completed_at: " . $completedAt->toDateTimeString());

            if ($completedAt->lte($cutoff)) {
                $project->delete();

                $this->info("Project {$project->id} soft deleted.");
                $count++;
            } else {
                $this->info("Project {$project->id} not yet eligible.");
            }
        }

        $this->info("Total soft deleted: {$count}");

        return Command::SUCCESS;
    }
}