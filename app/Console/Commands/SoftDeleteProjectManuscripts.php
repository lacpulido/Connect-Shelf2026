<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectManuscript;

class SoftDeleteProjectManuscripts extends Command
{
    protected $signature = 'manuscripts:soft-delete {--all} {--id=*}';
    protected $description = 'Soft delete Project Manuscripts';

    public function handle()
    {
        // OPTION 1: Delete all
        if ($this->option('all')) {
            $count = ProjectManuscript::query()->delete();

            $this->info("Soft deleted {$count} manuscripts.");
            return Command::SUCCESS;
        }

        // OPTION 2: Delete specific IDs
        $ids = $this->option('id');

        if (!empty($ids)) {
            $count = ProjectManuscript::whereIn('id', $ids)->delete();

            $this->info("Soft deleted {$count} selected manuscripts.");
            return Command::SUCCESS;
        }

        // OPTION 3: Interactive mode
        $this->warn('No option provided.');

        if ($this->confirm('Do you want to delete ALL manuscripts?')) {
            $count = ProjectManuscript::query()->delete();
            $this->info("Soft deleted {$count} manuscripts.");
        } else {
            $this->info('Operation cancelled.');
        }

        return Command::SUCCESS;
    }
}