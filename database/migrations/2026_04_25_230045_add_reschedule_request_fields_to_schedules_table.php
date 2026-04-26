<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->date('requested_defense_date')->nullable()->after('reschedule_requested');
            $table->text('reschedule_reason')->nullable()->after('requested_defense_date');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'requested_defense_date',
                'reschedule_reason',
            ]);
        });
    }
};