<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_preferred_advisers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->foreignId('adviser_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('preference_order');

            $table->timestamps();

            $table->unique(['project_id', 'adviser_id']);
            $table->unique(['project_id', 'preference_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_preferred_advisers');
    }
};