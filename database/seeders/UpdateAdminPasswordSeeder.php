<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UpdateAdminPasswordSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'admin@gmail.com')->update([
            'password' => Hash::make('Admin@12345'),
        ]);

        echo "\n✓ Admin password reset to: Admin@12345\n";
    }
}
