<?php

namespace Database\Seeders;

use App\Domain\Access\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Manajemen', 'code' => 'MGT', 'description' => 'Direktur, IT, dan super admin sistem'],
            ['name' => 'Registrasi', 'code' => 'REG', 'description' => 'Tim registrasi NIE dan produk ke BPOM'],
        ];

        foreach ($departments as $data) {
            Department::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
