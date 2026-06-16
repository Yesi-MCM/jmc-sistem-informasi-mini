<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndModuleSeeder::class,
            RegionSeeder::class,
            DepartmentAndPositionSeeder::class,
            EmployeeSeeder::class,
            UserSeeder::class,
            TransportAllowanceSettingSeeder::class,
        ]);
    }
}
