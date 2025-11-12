<?php

namespace Database\Seeders;

use App\Enums\User\UserRolesEnum;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
        ]);

        // User::factory(10)->create();

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@test.com'],
            [
                'name' => 'Super Admin',
                'password' => '12345678',
                'email_verified_at' => now(),
            ]
        );

        if (!$superAdmin->hasRole(UserRolesEnum::SuperAdmin->value)) {
            $superAdmin->assignRole(UserRolesEnum::SuperAdmin->value);
        }

        // Create Church Admin user
        $churchAdmin = User::firstOrCreate(
            ['email' => 'churchadmin@test.com'],
            [
                'name' => 'Church Admin',
                'password' => '12345678',
                'email_verified_at' => now(),
            ]
        );

        if (!$churchAdmin->hasRole(UserRolesEnum::ChurchAdmin->value)) {
            $churchAdmin->assignRole(UserRolesEnum::ChurchAdmin->value);
        }
    }
}
