<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('proposal_titles')->nullable()->after('title');
            $table->string('proposal_file')->nullable()->after('proposal_titles');
            $table->string('proposal_original_name')->nullable()->after('proposal_file');
            $table->unsignedTinyInteger('approved_proposal_index')->nullable()->after('proposal_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_titles',
                'proposal_file',
                'proposal_original_name',
                'approved_proposal_index',
            ]);
        });
    }
};