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
        Schema::create('forms', function (Blueprint $table) {
            $table->id(); // INT AUTO_INCREMENT PRIMARY KEY

            $table->string('title', 150);
            $table->string('file_name', 150);

            $table->unsignedBigInteger('department_id');

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->nullable()
                ->useCurrentOnUpdate();

            // Foreign Key
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};