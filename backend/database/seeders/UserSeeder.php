<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'employee_id' => null,
                'role_id' => 1, // Superadmin
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@jmc-mini.local',
                'cellphone' => '+6281111111111',
                'password' => Hash::make('password'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'employee_id' => 1, // Andri Eko
                'role_id' => 2, // Manager HRD
                'name' => 'Andri Eko Prasetyo',
                'username' => 'manager',
                'email' => 'andri.eko@jmc-mini.local',
                'cellphone' => '+6282218458888',
                'password' => Hash::make('password'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'employee_id' => 2, // Yessi Maria
                'role_id' => 3, // Admin HRD
                'name' => 'Yessi Maria',
                'username' => 'admin',
                'email' => 'yessi.maria@jmc-mini.local',
                'cellphone' => '+6281234567890',
                'password' => Hash::make('password'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(['id' => $user['id']], $user);
        }
    }
}
