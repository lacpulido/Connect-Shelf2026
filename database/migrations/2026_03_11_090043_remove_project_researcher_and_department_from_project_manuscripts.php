<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {

            // drop foreign keys first
            $table->dropForeign(['department_id']);
            $table->dropForeign(['project_researcher_id']);

            // then drop columns
            $table->dropColumn(['department_id', 'project_researcher_id']);
        });
    }

    public function down(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {

            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('project_researcher_id')->nullable()->constrained();
        });
    }
};