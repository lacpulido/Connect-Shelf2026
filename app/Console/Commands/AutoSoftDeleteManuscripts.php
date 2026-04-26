<?php

namespace App\Console\Commands;

use App\Models\ProjectManuscript;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoSoftDeleteManuscripts extends Command
{
    protected $signature = 'app:auto-soft-delete-manuscripts';
    protected $description = 'Soft delete approved manuscripts after 1 month';

    public function handle(): int
    {
        $cutoff = Carbon::now('Asia/Manila')->subMinute();

        $this->info('Cutoff (1 month ago): ' . $cutoff->toDateTimeString());

        $manuscripts = ProjectManuscript::query()
            ->where('status', 'approved') // important
            ->whereNull('deleted_at')
            ->get();

        $this->info('Matched manuscripts before cutoff check: ' . $manuscripts->count());

        $count = 0;

        foreach ($manuscripts as $manuscript) {
            // gamitin created_at OR pwede mong palitan ng approved_at kung meron
            $dateBasis = $manuscript->updated_at ?? $manuscript->created_at;

            $this->info("Manuscript {$manuscript->id} date: " . $dateBasis);

            if (Carbon::parse($dateBasis)->lte($cutoff)) {
                $manuscript->delete();

                $this->info("Manuscript {$manuscript->id} soft deleted.");
                $count++;
            } else {
                $this->info("Manuscript {$manuscript->id} not yet eligible.");
            }
        }

        $this->info("Total soft deleted: {$count}");

        return Command::SUCCESS;
    }
}