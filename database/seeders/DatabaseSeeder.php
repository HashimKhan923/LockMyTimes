<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the MAIN database with initial data.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);
    }
}