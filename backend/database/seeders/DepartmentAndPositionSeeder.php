<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentAndPositionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Departments
        $departments = [
            ['id' => 1, 'code' => 'MKT', 'name' => 'Marketing'],
            ['id' => 2, 'code' => 'HRD', 'name' => 'HRD'],
            ['id' => 3, 'code' => 'PRD', 'name' => 'Production'],
            ['id' => 4, 'code' => 'EXE', 'name' => 'Executive'],
            ['id' => 5, 'code' => 'COM', 'name' => 'Commissioner'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(['id' => $dept['id']], $dept);
        }

        // 2. Seed Positions
        $positions = [
            ['id' => 1, 'code' => 'MGR', 'name' => 'Manager', 'position_type' => 'manager'],
            ['id' => 2, 'code' => 'STF', 'name' => 'Staf', 'position_type' => 'staf'],
            ['id' => 3, 'code' => 'MGG', 'name' => 'Magang', 'position_type' => 'magang'],
        ];

        foreach ($positions as $pos) {
            DB::table('positions')->updateOrInsert(['id' => $pos['id']], $pos);
        }
    }
}
