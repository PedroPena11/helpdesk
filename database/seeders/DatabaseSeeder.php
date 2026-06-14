<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        if (!User::where('email', 'admin@helpdesk.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@helpdesk.com',
                'password' => Hash::make('SeguridadLaravel2026*'),
                'role' => 'admin',
            ]);
        }

        if (!User::where('email', 'tecnico@helpdesk.com')->exists()) {
            User::create([
                'name' => 'Carlos Mendoza (Técnico Redes)',
                'email' => 'tecnico@helpdesk.com',
                'password' => Hash::make('Tecnico2026*'),
                'role' => 'agent',
            ]);
        }

        if (!User::where('email', 'cliente@example.com')->exists()) {
            User::create([
                'name' => 'Juan Perez (Cliente)',
                'email' => 'cliente@example.com',
                'password' => Hash::make('Cliente2026*'),
                'role' => 'client',
            ]);
        }
    }
}