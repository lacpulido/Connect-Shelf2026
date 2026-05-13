<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE projects
            MODIFY COLUMN status ENUM(
                'Proposal',
                'Ongoing',
                'Completed'
            )
            NOT NULL
            DEFAULT 'Proposal'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE projects
            MODIFY COLUMN status ENUM(
                'Ongoing',
                'Completed'
            )
            NOT NULL
            DEFAULT 'Ongoing'
        ");
    }
};