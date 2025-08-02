<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resourcePermissionsScaffolding = [
            [
                'title' => ' Listing',
                'name' => '.index'
            ],
            [
                'title' => ' Add',
                'name' => '.create'
            ],
            [
                'title' => ' Save',
                'name' => '.store'
            ],
            [
                'title' => ' Edit',
                'name' => '.edit'
            ],
            [
                'title' => ' Update',
                'name' => '.update'
            ],
            [
                'title' => 'View',
                'name' => '.show'
            ],
            [
                'title' => ' Delete',
                'name' => '.destroy'
            ]
        ];

        $resourcePermissions = [
            [
                'title' => 'Users',
                'name' => 'users'
            ],
            [
                'title' => 'Roles',
                'name' => 'roles'
            ],
            [
                'title' => 'Categories',
                'name' => 'categories'
            ],
            [
                'title' => 'Products',
                'name' => 'products'
            ],
            [
                'title' => 'Engineers',
                'name' => 'engineers'
            ],
            [
                'title' => 'Co-ordinator',
                'name' => 'co-ordinators'
            ],
            [
                'title' => 'Technicians',
                'name' => 'technicians'
            ],
            [
                'title' => 'Customers',
                'name' => 'customers'
            ],
            [
                'title' => 'Departments',
                'name' => 'departments'
            ],
            [
                'title' => 'Expertise',
                'name' => 'expertises'
            ],
            [
                'title' => 'Job',
                'name' => 'jobs'
            ],
            [
                'title' => 'Requisition',
                'name' => 'requisitions'
            ]
        ];

        $extraPermissions = [
            [
                'title' => 'Job Status Change',
                'name' => 'jobs.change-status'
            ],
            [
                'title' => 'Job Reschedule',
                'name' => 'jobs.reschedule'
            ],
            [
                'title' => 'Job Status',
                'name' => 'jobs.change-status'
            ],
            [
                'title' => 'App Settings Information',
                'name' => 'settings.index'
            ],
            [
                'title' => 'App Settings Update',
                'name' => 'settings.update'
            ],
            [
                'title' => 'Job Settings Information',
                'name' => 'job.settings'
            ],
            [
                'title' => 'Job Settings Update',
                'name' => 'job.settings-update'
            ]
        ];

        $permissions = [];

        foreach ($resourcePermissions as $rP) {
            foreach ($resourcePermissionsScaffolding as $scaffold) {
                $permissions[] = [
                    'title' => $rP['title'] . $scaffold['title'],
                    'name' => $rP['name'] . $scaffold['name']
                ];
            }
        }

        $permissions = array_merge($permissions, $extraPermissions);

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }
    }
}