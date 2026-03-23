<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@cty.com.br'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Ctymil@2020'),
                'email_verified_at' => now(),
            ]
        );

        $role = Role::firstOrCreate(['name' => 'super_admin']);

        if (!$admin->hasRole($role)) {
            $admin->assignRole($role);
        }
    }
}
