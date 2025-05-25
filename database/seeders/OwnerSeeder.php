<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{

    public function run()
    {
        User::create([
            'first_name' => 'System',
            'last_name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('securepassword'),
            'user_role' => 'owner',
        ]);
    }
}
