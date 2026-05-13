<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_preferred_advisers', function (Blueprint $table) {
            if (! Schema::hasColumn('project_preferred_advisers', 'status')) {
                $table->string('status')->default('pending')->after('preference_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_preferred_advisers', function (Blueprint $table) {
            if (Schema::hasColumn('project_preferred_advisers', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};