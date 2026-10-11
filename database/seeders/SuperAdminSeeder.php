<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure Roles are seeded
        $this->call(RoleSeeder::class);

        $adminRole = Role::where('slug', 'super-admin')->first();

        // Create Super Admin User (for web guard/backward compatibility)
        User::updateOrCreate(
            ['email' => 'admin@teamtasker.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'), // You can change this later
                'role_id' => $adminRole->id,
                'is_approved' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create Master Admin (for admin guard)
        \App\Models\Admin::updateOrCreate(
            ['email' => 'admin@teamtasker.com'],
            [
                'name' => 'System Control Admin',
                'password' => Hash::make('admin123'),
            ]
        );
    }
}
