<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CollegeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('colleges')->insert([
            'name' => 'College of Computing and Information Sciences',
            'acronym' => 'CCIS',
            'created_at' => now(),
            'updated_at' => now(),
            
        ]);
    }
}