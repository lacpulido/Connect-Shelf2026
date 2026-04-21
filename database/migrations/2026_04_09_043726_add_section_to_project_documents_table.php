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

            // ✅ Add section column
            $table->string('section')
                  ->nullable()
                  ->after('title'); // adjust position if needed

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {

            // ❌ Remove column if rollback
            $table->dropColumn('section');

        });
    }
};
