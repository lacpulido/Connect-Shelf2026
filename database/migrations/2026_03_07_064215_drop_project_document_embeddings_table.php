<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_document_embeddings');
    }

    public function down(): void
    {
        Schema::create('project_document_embeddings', function ($table) {
            $table->id();
            $table->timestamps();
        });
    }
};
