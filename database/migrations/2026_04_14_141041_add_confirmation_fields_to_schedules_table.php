<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'status')) {
                $table->string('status')->default('scheduled')->after('venue');
            }

            if (!Schema::hasColumn('schedules', 'is_confirmed')) {
                $table->boolean('is_confirmed')->default(false)->after('status');
            }

            if (!Schema::hasColumn('schedules', 'reschedule_requested')) {
                $table->boolean('reschedule_requested')->default(false)->after('is_confirmed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasColumn('schedules', 'reschedule_requested')) {
                $table->dropColumn('reschedule_requested');
            }

            if (Schema::hasColumn('schedules', 'is_confirmed')) {
                $table->dropColumn('is_confirmed');
            }

            if (Schema::hasColumn('schedules', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};