<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        // Create default admin users with secure passwords
        $admins = [
            [
                'name' => 'Darwin',
                'first_surname' => 'Nuñez',
                'second_surname' => 'Chavarría',
                'gmail' => 'darwin990@gmail.com',
                'password' => Hash::make('Darwin1234$'),
                'last_access' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Aaron',
                'first_surname' => 'User',
                'second_surname' => null,
                'gmail' => 'aaron@gmail.com',
                'password' => Hash::make('Aaron1234$'),
                'last_access' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arturo',
                'first_surname' => 'Chavarría',
                'second_surname' => null,
                'gmail' => 'arturo01097@gmail.com',
                'password' => Hash::make('12345678'),
                'last_access' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Administrador',
                'first_surname' => 'Sistema',
                'second_surname' => null,
                'gmail' => 'admin@cicloperez.com',
                'password' => Hash::make('Admin2024!@#'),
                'last_access' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($admins as $data) {
            $existing = DB::table('admins')->where('gmail', $data['gmail'])->first();
            if ($existing) {
                $this->command->warn("El admin {$data['gmail']} ya existe en la base de datos.");

                continue;
            }
            DB::table('admins')->insert($data);
            $this->command->info("✅ Admin creado: {$data['gmail']}");
            $this->command->line("🔑 Contraseña: {$data['password']}");
        }
        $this->command->warn('⚠️  IMPORTANTE: Cambia la contraseña después del primer inicio de sesión.');
    }
}
