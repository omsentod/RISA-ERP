<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $adminRegistrasi = Role::firstOrCreate(['name' => 'admin_registrasi', 'guard_name' => 'web']);
        $adminRegistrasi->syncPermissions(
            Permission::query()
                ->where(function ($query) {
                    $query->where('name', 'like', '%_product')
                        ->orWhere('name', 'like', '%_any_product')
                        ->orWhere('name', 'like', '%product::category')
                        ->orWhere('name', 'like', '%_registration')
                        ->orWhere('name', 'like', '%_any_registration')
                        ->orWhere('name', 'like', 'widget_%')
                        ->orWhere('name', 'like', 'page_%');
                })
                ->where('name', 'not like', 'force_delete%')
                ->get()
        );
    }
}
