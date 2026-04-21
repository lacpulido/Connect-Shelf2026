<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $collegeId = DB::table('colleges')
            ->where('acronym', 'CCIS')
            ->value('id');

        DB::table('departments')->insert([
            [
                'name' => 'Information Technology',
                'college_id' => $collegeId,
                'created_at' => now(),
                'updated_at' => now(),
                
            ],
            [
                'name' => 'Computer Science',
                'college_id' => $collegeId,
                'created_at' => now(),
                'updated_at' => now(),
                
            ],
        ]);
    }
}
