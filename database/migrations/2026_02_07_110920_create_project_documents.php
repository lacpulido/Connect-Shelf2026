<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->string('filename', 255);
            $table->text('description')->nullable();

            $table->timestamps(); // created_at = date of submission
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};
