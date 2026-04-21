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
        $table->unsignedInteger('downloads')->default(0);
    });
}

public function down()
{
    Schema::table('project_manuscripts', function (Blueprint $table) {
        $table->dropColumn('downloads');
    });
}
};
