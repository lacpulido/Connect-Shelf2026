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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 250)->index();
           
            $table->string('keywords')->nullable()->index();
            $table->enum('project_type', ['Thesis', 'Capstone'])->index();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('college_id')->constrained('colleges')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('academic_year', 10);
            $table->date('end_date')->nullable();
            $table->enum('status', ['Ongoing', 'Completed'])->default('ongoing')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
