<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {

            $table->foreignId('department_id')
                ->after('project_id')
                ->constrained('departments')
                ->cascadeOnDelete();

            $table->foreignId('project_researcher_id')
                ->after('department_id')
                ->constrained('project_researchers')
                ->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {

            $table->dropForeign(['department_id']);
            $table->dropForeign(['project_researcher_id']);

            $table->dropColumn(['department_id', 'project_researcher_id']);
        });
    }
};