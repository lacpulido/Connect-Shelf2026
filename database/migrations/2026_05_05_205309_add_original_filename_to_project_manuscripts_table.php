<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {
            if (! Schema::hasColumn('project_manuscripts', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('filename');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_manuscripts', function (Blueprint $table) {
            if (Schema::hasColumn('project_manuscripts', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
        });
    }
};