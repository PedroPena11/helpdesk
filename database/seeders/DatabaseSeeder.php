<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if(!User::where('email','admin@helpdesk.com')->exists()){
            User::create([
                'name' => 'Administrator Support',
                'email' => 'admin@helpdesk.com',
                'password' => Hash::make('SeguridadLaravel2026*'),
            ]);
        }

    }
}
