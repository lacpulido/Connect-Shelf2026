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
        Schema::table('notifications', function (Blueprint $table) {

    $table->string('type')->nullable()->after('message'); 
    $table->unsignedBigInteger('reference_id')->nullable()->after('type');
    $table->string('reference_type')->nullable()->after('reference_id');

    $table->index(['reference_id', 'reference_type']);
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
