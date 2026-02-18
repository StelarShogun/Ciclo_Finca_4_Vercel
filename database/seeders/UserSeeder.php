<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario administrador
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8888',
            'address' => 'Dirección de prueba'
        ]);

        // Crear algunos usuarios de prueba
        User::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8889',
            'address' => 'San José, Costa Rica'
        ]);

        User::create([
            'name' => 'María González',
            'email' => 'maria@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8890',
            'address' => 'Cartago, Costa Rica'
        ]);
    }
}
