<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios de ejemplo
        $usuarios = [
            [
                'nombre' => 'Darwin',
                'apellido' => 'User',
                'email' => 'darwin@gmail.com',
                'password' => Hash::make('Darwin1234$'),
                'rol' => 'admin',
            ],
            [
                'nombre' => 'Aaron',
                'apellido' => 'User',
                'email' => 'aaron@gmail.com',
                'password' => Hash::make('Aaron1234$'),
                'rol' => 'admin',
            ],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'email' => 'admin@cicloperez.com',
                'password' => Hash::make('Admin2024!@#'),
                'rol' => 'admin',
            ],
        ];

        foreach ($usuarios as $data) {
            $existing = Usuario::where('email', $data['email'])->first();
            if ($existing) {
                $this->command->warn("El usuario {$data['email']} ya existe en la base de datos.");
                continue;
            }
            Usuario::create($data);
            $this->command->info("✅ Usuario creado: {$data['email']}");
            $this->command->line("🔑 Contraseña: {$data['password']}");
        }
        $this->command->warn('⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión.');
    }
}

