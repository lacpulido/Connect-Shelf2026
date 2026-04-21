<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Project being scheduled
            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            // Focal person who created the schedule
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Defense details
            $table->date('defense_date');
            $table->time('defense_time');
            $table->string('venue')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};