<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'preferred_adviser_ids')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->json('preferred_adviser_ids')->nullable()->after('adviser_id');
            });
        }

        if (! Schema::hasColumn('projects', 'adviser_status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('adviser_status')->nullable()->after('preferred_adviser_ids');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'adviser_status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('adviser_status');
            });
        }

        if (Schema::hasColumn('projects', 'preferred_adviser_ids')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('preferred_adviser_ids');
            });
        }
    }
};