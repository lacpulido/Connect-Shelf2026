<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectManuscript;

class RestoreProjectManuscripts extends Command
{
    protected $signature = 'manuscripts:restore {--all} {--id=*}';
    protected $description = 'Restore soft deleted Project Manuscripts';

    public function handle()
    {
        if ($this->option('all')) {
            $count = ProjectManuscript::onlyTrashed()->restore();
            $this->info("Restored {$count} manuscripts.");
            return Command::SUCCESS;
        }

        $ids = $this->option('id');

        if (!empty($ids)) {
            $count = ProjectManuscript::onlyTrashed()
                ->whereIn('id', $ids)
                ->restore();

            $this->info("Restored {$count} selected manuscripts.");
            return Command::SUCCESS;
        }

        $this->warn('No option provided.');

        if ($this->confirm('Do you want to restore ALL soft deleted manuscripts?')) {
            $count = ProjectManuscript::onlyTrashed()->restore();
            $this->info("Restored {$count} manuscripts.");
        } else {
            $this->info('Operation cancelled.');
        }

        return Command::SUCCESS;
    }
}