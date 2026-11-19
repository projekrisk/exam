<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => User::DEFAULT_TEACHER_PASSWORD,
                'must_change_password' => false,
                'is_active' => true,
            ]
        );
    }
}
