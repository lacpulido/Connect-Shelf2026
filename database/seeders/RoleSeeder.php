<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; 

class RoleSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['name' => 'Administrator'],
            ['name' => 'Panel Member'],
            ['name' => 'Focal Person'],
            ['name' => 'Department Chairperson'],
            ['name' => 'Research Coordinator'],
            ['name' => 'Adviser'],
            
         
        ]);
    }
}
