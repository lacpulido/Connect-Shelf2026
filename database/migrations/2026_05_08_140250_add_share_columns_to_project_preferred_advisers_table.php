<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_preferred_advisers', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('status');
            $table->timestamp('shared_at')->nullable()->after('is_shared');
        });
    }

    public function down(): void
    {
        Schema::table('project_preferred_advisers', function (Blueprint $table) {
            $table->dropColumn(['is_shared', 'shared_at']);
        });
    }
};