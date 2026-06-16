<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Provinces
        $provinces = [
            ['id' => 1, 'code' => '34', 'name' => 'Daerah Istimewa Yogyakarta'],
            ['id' => 2, 'code' => '31', 'name' => 'DKI Jakarta'],
            ['id' => 3, 'code' => '32', 'name' => 'Jawa Barat'],
        ];

        foreach ($provinces as $prov) {
            DB::table('provinces')->updateOrInsert(['id' => $prov['id']], $prov);
        }

        // 2. Seed Regencies
        $regencies = [
            ['id' => 1, 'province_id' => 1, 'code' => '3404', 'name' => 'Kabupaten Sleman'],
            ['id' => 2, 'province_id' => 1, 'code' => '3402', 'name' => 'Kabupaten Bantul'],
            ['id' => 3, 'province_id' => 2, 'code' => '3171', 'name' => 'Kota Jakarta Pusat'],
            ['id' => 4, 'province_id' => 3, 'code' => '3204', 'name' => 'Kabupaten Bandung'],
        ];

        foreach ($regencies as $reg) {
            DB::table('regencies')->updateOrInsert(['id' => $reg['id']], $reg);
        }

        // 3. Seed Districts
        $districts = [
            ['id' => 1, 'regency_id' => 1, 'code' => '340401', 'name' => 'Kecamatan Depok'],
            ['id' => 2, 'regency_id' => 1, 'code' => '340402', 'name' => 'Kecamatan Mlati'],
            ['id' => 3, 'regency_id' => 2, 'code' => '340201', 'name' => 'Kecamatan Kasihan'],
            ['id' => 4, 'regency_id' => 3, 'code' => '317101', 'name' => 'Kecamatan Gambir'],
            ['id' => 5, 'regency_id' => 4, 'code' => '320401', 'name' => 'Kecamatan Lembang'],
        ];

        foreach ($districts as $dist) {
            DB::table('districts')->updateOrInsert(['id' => $dist['id']], $dist);
        }
    }
}
