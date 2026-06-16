<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'id' => 1,
                'nip' => '19900101',
                'name' => 'Andri Eko Prasetyo',
                'email' => 'andri.eko@jmc-mini.local',
                'phone' => '+6282218458888',
                'birth_place' => 'Sleman',
                'birth_date' => '1990-01-01',
                'marital_status' => 'kawin',
                'children_count' => 2,
                'joined_at' => '2015-05-10',
                'position_id' => 1, // Manager
                'department_id' => 2, // HRD
                'employment_type' => 'pkwtt', // Tetap
                'gender' => 'pria',
                'distance_km' => 15.50,
                'district_id' => 1, // Depok
                'full_address' => 'Jl. Kaliurang KM 5, Sleman, DIY',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nip' => '19920202',
                'name' => 'Yessi Maria',
                'email' => 'yessi.maria@jmc-mini.local',
                'phone' => '+6281234567890',
                'birth_place' => 'Yogyakarta',
                'birth_date' => '1992-02-15',
                'marital_status' => 'tidak kawin',
                'children_count' => 0,
                'joined_at' => '2020-03-01',
                'position_id' => 2, // Staf
                'department_id' => 2, // HRD
                'employment_type' => 'pkwt', // Kontrak
                'gender' => 'wanita',
                'distance_km' => 3.20,
                'district_id' => 2, // Mlati
                'full_address' => 'Jl. Magelang KM 7, Sleman, DIY',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nip' => '19950303',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@jmc-mini.local',
                'phone' => '+6287890123456',
                'birth_place' => 'Bantul',
                'birth_date' => '1995-07-20',
                'marital_status' => 'tidak kawin',
                'children_count' => 0,
                'joined_at' => '2023-01-15',
                'position_id' => 3, // Magang
                'department_id' => 3, // Production
                'employment_type' => 'magang', // Magang
                'gender' => 'pria',
                'distance_km' => 26.00,
                'district_id' => 3, // Kasihan
                'full_address' => 'Jl. Bantul KM 4, Bantul, DIY',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nip' => '19880404',
                'name' => 'Indah Permata',
                'email' => 'indah.permata@jmc-mini.local',
                'phone' => '+6285678901234',
                'birth_place' => 'Jakarta',
                'birth_date' => '1988-11-12',
                'marital_status' => 'kawin',
                'children_count' => 1,
                'joined_at' => '2012-06-01',
                'position_id' => 2, // Staf
                'department_id' => 1, // Marketing
                'employment_type' => 'pkwtt', // Tetap
                'gender' => 'wanita',
                'distance_km' => 20.00,
                'district_id' => 4, // Gambir
                'full_address' => 'Jl. Monas No. 10, Jakarta Pusat',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'nip' => '19850505',
                'name' => 'Roni Wijaya',
                'email' => 'roni.wijaya@jmc-mini.local',
                'phone' => '+6281112223334',
                'birth_place' => 'Sleman',
                'birth_date' => '1985-05-05',
                'marital_status' => 'kawin',
                'children_count' => 3,
                'joined_at' => '2010-01-01',
                'position_id' => 1, // Manager
                'department_id' => 4, // Executive
                'employment_type' => 'pkwtt', // Tetap
                'gender' => 'pria',
                'distance_km' => 5.00,
                'district_id' => 1, // Depok
                'full_address' => 'Jl. Gejayan No. 25, Sleman, DIY',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($employees as $emp) {
            DB::table('employees')->updateOrInsert(['id' => $emp['id']], $emp);
        }

        // Seed some educational records
        $educations = [
            ['employee_id' => 1, 'education_level' => 'SMA', 'school_name' => 'SMA Negeri 1 Yogyakarta', 'graduation_year' => 2008, 'sort_order' => 1],
            ['employee_id' => 1, 'education_level' => 'S1', 'school_name' => 'Universitas Gadjah Mada', 'graduation_year' => 2012, 'sort_order' => 2],
            ['employee_id' => 2, 'education_level' => 'SMA', 'school_name' => 'SMA Negeri 3 Yogyakarta', 'graduation_year' => 2010, 'sort_order' => 1],
            ['employee_id' => 2, 'education_level' => 'S1', 'school_name' => 'Universitas Negeri Yogyakarta', 'graduation_year' => 2014, 'sort_order' => 2],
            ['employee_id' => 3, 'education_level' => 'SMA', 'school_name' => 'SMA Negeri 2 Bantul', 'graduation_year' => 2013, 'sort_order' => 1],
            ['employee_id' => 3, 'education_level' => 'S1', 'school_name' => 'Universitas Amikom Yogyakarta', 'graduation_year' => 2017, 'sort_order' => 2],
        ];

        foreach ($educations as $index => $edu) {
            DB::table('employee_educations')->updateOrInsert(
                ['employee_id' => $edu['employee_id'], 'education_level' => $edu['education_level']],
                array_merge($edu, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
