<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            // save 3 uploaded files
            if (! Schema::hasColumn('projects', 'proposal_files')) {
                $table->json('proposal_files')
                    ->nullable()
                    ->after('proposal_titles');
            }

            // save original filenames
            if (! Schema::hasColumn('projects', 'proposal_original_names')) {
                $table->json('proposal_original_names')
                    ->nullable()
                    ->after('proposal_files');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {

            if (Schema::hasColumn('projects', 'proposal_original_names')) {
                $table->dropColumn('proposal_original_names');
            }

            if (Schema::hasColumn('projects', 'proposal_files')) {
                $table->dropColumn('proposal_files');
            }
        });
    }
};