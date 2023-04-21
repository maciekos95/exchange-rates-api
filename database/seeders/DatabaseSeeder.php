<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Permission::create(['name' => 'create-users']);
        Permission::create(['name' => 'edit-users']);
        Permission::create(['name' => 'delete-users']);
        Permission::create(['name' => 'add-currency-rates']);
        Permission::create(['name' => 'update-currency-rates']);
        Permission::create(['name' => 'delete-currency-rates']);
        Permission::create(['name' => 'get-currency-rates']);

        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);
        $userRole = Role::create(['name' => 'user']);

        $adminRole->givePermissionTo([
            'create-users',
            'edit-users',
            'delete-users',
            'add-currency-rates',
            'update-currency-rates',
            'delete-currency-rates',
            'get-currency-rates',
        ]);

        $editorRole->givePermissionTo([
            'add-currency-rates',
            'get-currency-rates',
        ]);

        $userRole->givePermissionTo([
            'get-currency-rates',
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('defaultPassword'),
        ]);
        $admin->assignRole($adminRole);

        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('defaultPassword'),
        ]);
        $editor->assignRole($editorRole);

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('defaultPassword'),
        ]);
        $user->assignRole($userRole);
    }
}
