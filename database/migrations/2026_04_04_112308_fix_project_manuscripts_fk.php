<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('project_manuscripts', function (Blueprint $table) {

        // drop old FK
        $table->dropForeign(['project_id']);

        // re-add WITHOUT cascade
        $table->foreign('project_id')
            ->references('id')
            ->on('projects')
            ->restrictOnDelete(); // ✅ IMPORTANT
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
