<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Roles
        $roles = [
            ['id' => 1, 'code' => 'superadmin', 'name' => 'Superadmin', 'description' => 'Super Administrator with system control'],
            ['id' => 2, 'code' => 'manager_hrd', 'name' => 'Manager HRD', 'description' => 'HRD Manager overseeing summaries and approvals'],
            ['id' => 3, 'code' => 'admin_hrd', 'name' => 'Admin HRD', 'description' => 'HRD Administrator performing operations and settings'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['id' => $role['id']], $role);
        }

        // 2. Seed Modules
        $modules = [
            ['id' => 1, 'code' => 'dashboard', 'name' => 'Dashboard', 'description' => 'Aplikasi dashboard utama', 'sort_order' => 1],
            ['id' => 2, 'code' => 'role', 'name' => 'Kelola Role', 'description' => 'Modul RBAC review', 'sort_order' => 2],
            ['id' => 3, 'code' => 'user', 'name' => 'Kelola User', 'description' => 'Modul manajemen user sistem', 'sort_order' => 3],
            ['id' => 4, 'code' => 'profile', 'name' => 'My Profile', 'description' => 'Modul profil mandiri', 'sort_order' => 4],
            ['id' => 5, 'code' => 'pegawai', 'name' => 'Modul Data Pegawai', 'description' => 'Modul CRUD pegawai', 'sort_order' => 5],
            ['id' => 6, 'code' => 'presensi', 'name' => 'Modul Presensi', 'description' => 'Modul rekap dan detail kehadiran', 'sort_order' => 6],
            ['id' => 7, 'code' => 'tunjangan_transport', 'name' => 'Modul Tunjangan Transport', 'description' => 'Modul perhitungan tunjangan transport bulanan', 'sort_order' => 7],
            ['id' => 8, 'code' => 'setting_tunjangan', 'name' => 'Setting Tunjangan Transport', 'description' => 'Modul konfigurasi tarif dan batasan tunjangan', 'sort_order' => 8],
            ['id' => 9, 'code' => 'log', 'name' => 'Modul Log', 'description' => 'Modul audit logs aktivitas', 'sort_order' => 9],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(['id' => $module['id']], $module);
        }

        // 3. Seed Role Permissions
        // Define fixed permissions mapping
        $permissions = [
            // Superadmin (Role 1)
            [
                'role_id' => 1, 'module_id' => 1, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Dashboard
            [
                'role_id' => 1, 'module_id' => 2, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Kelola Role (R only)
            [
                'role_id' => 1, 'module_id' => 3, 'can_access' => true, 'can_create' => true,
                'read_scope' => 'all', 'update_scope' => 'all', 'delete_scope' => 'all'
            ], // Kelola User (CRUD)
            [
                'role_id' => 1, 'module_id' => 4, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'own', 'update_scope' => 'own', 'delete_scope' => 'no'
            ], // My Profile (RO, UO)
            [
                'role_id' => 1, 'module_id' => 9, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Modul Log (R only)

            // Manager HRD (Role 2)
            [
                'role_id' => 2, 'module_id' => 1, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Dashboard
            [
                'role_id' => 2, 'module_id' => 4, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'own', 'update_scope' => 'own', 'delete_scope' => 'no'
            ], // My Profile (RO, UO)
            [
                'role_id' => 2, 'module_id' => 5, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Modul Data Pegawai (R only)
            [
                'role_id' => 2, 'module_id' => 6, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Modul Presensi (R only)
            [
                'role_id' => 2, 'module_id' => 7, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Modul Tunjangan Transport (RO)

            // Admin HRD (Role 3)
            [
                'role_id' => 3, 'module_id' => 1, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Dashboard
            [
                'role_id' => 3, 'module_id' => 4, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'own', 'update_scope' => 'own', 'delete_scope' => 'no'
            ], // My Profile (RO, UO)
            [
                'role_id' => 3, 'module_id' => 5, 'can_access' => true, 'can_create' => true,
                'read_scope' => 'all', 'update_scope' => 'all', 'delete_scope' => 'all'
            ], // Modul Data Pegawai (CRUD)
            [
                'role_id' => 3, 'module_id' => 6, 'can_access' => true, 'can_create' => true,
                'read_scope' => 'all', 'update_scope' => 'all', 'delete_scope' => 'all'
            ], // Modul Presensi (CRUD)
            [
                'role_id' => 3, 'module_id' => 7, 'can_access' => true, 'can_create' => false,
                'read_scope' => 'all', 'update_scope' => 'no', 'delete_scope' => 'no'
            ], // Modul Tunjangan Transport (RO)
            [
                'role_id' => 3, 'module_id' => 8, 'can_access' => true, 'can_create' => true,
                'read_scope' => 'all', 'update_scope' => 'all', 'delete_scope' => 'all'
            ], // Setting Tunjangan Transport (CRUD)
        ];

        foreach ($permissions as $perm) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $perm['role_id'], 'module_id' => $perm['module_id']],
                $perm
            );
        }
    }
}
