<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = 'admin@example.com';

        $user = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'first_name' => 'Admin',
                'middle_name' => null,
                'last_name' => 'User',
                'extension_name' => null,
                'college_id' => null,
                'department_id' => null,
                'user_type' => 1,
                'expertise' => null,
                'status' => 'Active',
                'is_active' => true,
                'password' => Hash::make('adminpassword123'),
            ]
        );

        $adminRole = DB::table('roles')->where('name', 'Administrator')->first();

        if (!$adminRole) {
            $this->command->error('Administrator role does not exist! Please seed roles first.');
            return;
        }

        $exists = DB::table('role_user')
            ->where('user_id', $user->id)
            ->where('role_id', $adminRole->id)
            ->exists();

        if (!$exists) {
            DB::table('role_user')->insert([
                'user_id' => $user->id,
                'role_id' => $adminRole->id,
            ]);
        }
    }
}