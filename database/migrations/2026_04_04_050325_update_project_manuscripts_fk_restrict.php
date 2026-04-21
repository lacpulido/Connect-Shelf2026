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
            // Drop existing foreign key
            $table->dropForeign(['project_id']);

            // Re-add with RESTRICT
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {
            // Drop restrict constraint
            $table->dropForeign(['project_id']);

            // Restore cascade behavior
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->cascadeOnDelete();
        });
    }
};