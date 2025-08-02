<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'title' => 'Admin',
                'name' => 'admin',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Engineer',
                'name' => 'engineer',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Job Coordinator',
                'name' => 'job-coordinator',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Technician',
                'name' => 'technician',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Customer',
                'name' => 'customer',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Sub Contracter',
                'name' => 'sub-contractor',
                'is_sytem_role' => 1
            ],
            [
                'title' => 'Vendor',
                'name' => 'vendor',
                'is_sytem_role' => 1
            ]
        ];

        foreach ($roles as $role) {
            \Spatie\Permission\Models\Role::updateOrCreate([
                'name' => $role['name']
            ], $role);
        }

        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();

        if ($adminRole) {
            $adminRole->syncPermissions(\Spatie\Permission\Models\Permission::pluck('id')->toArray());
        }

    }
}
