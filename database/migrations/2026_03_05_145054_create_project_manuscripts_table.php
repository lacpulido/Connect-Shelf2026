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
       Schema::create('project_manuscripts', function (Blueprint $table) {
    $table->id();

    $table->foreignId('project_id')
        ->constrained('projects')
        ->cascadeOnDelete();

    $table->string('title');

    $table->text('abstract')->nullable();

    $table->longText('content')->nullable();

    $table->json('embedding')->nullable();

    $table->timestamps();
}); 
    }
};
