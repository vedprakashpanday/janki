<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Master Admin',
            'email' => 'admin@jankivilla.com',
            'phone' => '9876543210',
            'password' => Hash::make('password123'), // Ye password yaad rakhiyega
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
