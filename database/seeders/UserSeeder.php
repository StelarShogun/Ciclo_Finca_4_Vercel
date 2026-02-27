<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario administrador
        Usuario::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8888',
            'address' => 'Dirección de prueba'
        ]);

        // Crear algunos usuarios de prueba
        Usuario::create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8889',
            'address' => 'San José, Costa Rica'
        ]);

        Usuario::create([
            'name' => 'María González',
            'email' => 'maria@example.com',
            'password' => Hash::make('password'),
            'phone' => '8888-8890',
            'address' => 'Cartago, Costa Rica'
        ]);
    }
}
