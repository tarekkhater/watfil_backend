<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GovernorateSeeder::class,
            SuperAdminSeeder::class,
            CommissionRuleSeeder::class,
            ProductTypeSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
