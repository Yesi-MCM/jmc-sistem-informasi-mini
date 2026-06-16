<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransportAllowanceSettingSeeder extends Seeder
{
    public function run(): void
    {
        $setting = [
            'id' => 1,
            'base_fare' => 5000.00,
            'effective_start' => '2026-01-01',
            'min_km' => 5.00,
            'max_km' => 25.00,
            'is_active' => true,
            'created_by' => 1, // Superadmin
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('transport_allowance_settings')->updateOrInsert(['id' => 1], $setting);
    }
}
