<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\SuperAdmin;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::firstOrCreate(
            ['email' => 'admin@watafl.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Admin@1234'),
            ]
        );
    }
}
