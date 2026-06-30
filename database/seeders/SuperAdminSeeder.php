<?php

namespace Database\Seeders;

use App\Models\Main\SuperAdmin;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::updateOrCreate(
            ['email' => 'admin@lockmytimes.com'],
            [
                'name'              => 'Lockmytimes Owner',
                'password'          => 'password',     // hashed automatically by cast
                'role'              => 'owner',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );
    }
}