<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id(); // BIGINT auto-increment (default)

            $table->foreignId('project_document_id')
                  ->constrained('project_documents')
                  ->cascadeOnDelete();

            $table->foreignId('adviser_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
