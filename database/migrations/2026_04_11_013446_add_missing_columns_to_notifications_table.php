<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('notifications', 'type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('type')->nullable()->after('message');
            });
        }

        if (!Schema::hasColumn('notifications', 'reference_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('type');
            });
        }

        if (!Schema::hasColumn('notifications', 'reference_type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('reference_type')->nullable()->after('reference_id');
            });
        }

        if (!Schema::hasColumn('notifications', 'meta')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('reference_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications', 'meta')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }

        if (Schema::hasColumn('notifications', 'reference_type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('reference_type');
            });
        }

        if (Schema::hasColumn('notifications', 'reference_id')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('reference_id');
            });
        }

        if (Schema::hasColumn('notifications', 'type')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};