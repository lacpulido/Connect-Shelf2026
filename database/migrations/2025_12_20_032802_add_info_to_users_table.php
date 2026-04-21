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
        Schema::table('users', function (Blueprint $table) {

            $table->string('first_name', 200);
            $table->string('middle_name', 200)->nullable();
            $table->string('last_name', 200);
            $table->string('extension_name', 10)->nullable();

            // FK columns MUST be nullable first
            $table->foreignId('college_id')
                  ->nullable()
                  ->constrained('colleges')
                  ->cascadeOnDelete();

            $table->foreignId('department_id')
                  ->nullable()
                  ->constrained('departments')
                  ->cascadeOnDelete();

            $table->tinyInteger('user_type')
                  ->comment('1 = Faculty, 2 = Student');

            $table->longText('expertise')->nullable()->after('user_type');

            $table->enum('status', ['Active', 'Inactive'])
                  ->default('Active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Drop foreign keys first
            $table->dropForeign(['college_id']);
            $table->dropForeign(['department_id']);

            // Drop columns
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'extension_name',
                'college_id',
                'department_id',
                'user_type',
                'expertise',
                'status',
            ]);
        });
    }
};