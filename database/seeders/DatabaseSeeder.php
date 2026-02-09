<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tuya.local')],
            [
                'name' => 'Admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', 'admin123')),
            ]
        );
    }
}
