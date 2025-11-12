<?php

namespace Database\Seeders;

use App\Enums\User\UserRolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles from UserRolesEnum
        foreach (UserRolesEnum::cases() as $role) {
            Role::firstOrCreate(
                ['name' => $role->value],
                ['guard_name' => 'web']
            );
        }
    }
}
