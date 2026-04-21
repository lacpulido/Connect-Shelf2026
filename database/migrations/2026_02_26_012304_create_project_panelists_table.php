<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_panelists', function (Blueprint $table) {

            $table->id();

            // Project reference
            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            // Panelist (User reference)
            $table->foreignId('panelist_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            // Prevent duplicate panelist per project
            $table->unique(['project_id', 'panelist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_panelists');
    }
};