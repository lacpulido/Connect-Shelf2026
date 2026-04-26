<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use Carbon\Carbon;

class AutoSoftDeleteProjects extends Command
{
    protected $signature = 'app:auto-soft-delete-projects';
    protected $description = 'Soft delete completed projects automatically';

    public function handle()
    {
        // Adjust days if needed (example: 1 day after completion)
        $projects = Project::where('status', 'Completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', Carbon::now()->subDays(1))
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects to soft delete.');
            return;
        }

        foreach ($projects as $project) {
            $project->delete(); // uses SoftDeletes
        }

        $this->info('Completed projects soft deleted successfully.');
    }
}