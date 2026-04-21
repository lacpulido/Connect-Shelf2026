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
    Schema::table('project_documents', function (Blueprint $table) {
        $table->integer('version')->default(1);
        $table->boolean('is_current')->default(true)->after('version');
        $table->unsignedBigInteger('parent_id')->nullable()->after('id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            //
        });
    }
};
