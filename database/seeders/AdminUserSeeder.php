<?php

namespace Database\Seeders;

use App\Domain\Access\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $manajemen = Department::where('name', 'Manajemen')->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@risa.co.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'department_id' => $manajemen?->id,
                'is_active' => true,
            ]
        );

        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
    }
}
