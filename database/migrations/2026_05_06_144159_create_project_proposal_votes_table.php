<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_proposal_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('proposal_index');
            $table->timestamps();

            $table->unique(['project_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposal_votes');
    }
};