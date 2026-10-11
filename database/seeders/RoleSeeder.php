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
        $allPermissions = [];
        foreach (config('permissions') as $group => $permissions) {
            if (is_array($permissions)) {
                foreach ($permissions as $action => $label) {
                    $allPermissions[] = "$group.$action";
                }
            } else {
                $allPermissions[] = $group;
            }
        }

        $managerPermissions = [
            'dashboard.view',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'tasks.my_tasks',
            'tasks.view',
            'tasks.create',
            'tasks.edit',
            'tickets.view',
            'tickets.reply',
            'tickets.assign',
            'clients.view',
            'clients.create',
            'clients.edit',
            'chat.view',
            'users.view',
            'users.create',
            'users.edit',
            'users.approve',
            'roles.view',
            'meetings.view',
            'meetings.create',
            'meetings.join',
            'meetings.cancel',
        ];

        $clientPermissions = [
            'dashboard.view',
            'projects.view',
            'tasks.my_tasks',
            'tasks.view',
            'tasks.create',
            'tasks.edit',
            'chat.view',
        ];

        $hrManagerPermissions = [
            'dashboard.view',
            'hr.view',
            'hr.manage',
            'hr.attendance_settings',
            'hr.shift_rules',
            'hr.leave_requests',
            'hr.work_reports',
            'hr.timesheets',
            'hr.staff',
            'hr.roles',
            'users.view',
            'users.create',
            'users.edit',
            'users.approve',
            'roles.view',
            'roles.create',
            'roles.edit',
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.punch',
            'attendance.submit',
            'attendance.approve',
            'attendance.reject',
            'attendance.report',
            'attendance.export',
            'attendance.manage',
            'attendance.dashboard_overview',
            'attendance.daily',
            'attendance.monthly',
            'attendance.calendar_all',
            'attendance.requests_manage',
            'attendance.reports',
            'attendance.settings',
            'settings.view',
        ];

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'permissions' => $allPermissions],
            ['name' => 'HR Manager', 'slug' => 'hr-manager', 'permissions' => $hrManagerPermissions],
            ['name' => 'Manager', 'slug' => 'manager', 'permissions' => $managerPermissions],
            ['name' => 'Client', 'slug' => 'client', 'permissions' => $clientPermissions],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'permissions' => $role['permissions'],
                ]
            );
        }
    }
}
