<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Usuario;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Semillas base
        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            ProductsSeeder::class,
            AdminSeeder::class,
            ClientUserSeeder::class,
        ]);

        // Crear un admin en tabla usuarios si no existe
        if (!Usuario::where('email', 'admin@example.com')->exists()) {
            Usuario::create([
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'email' => 'admin@example.com',
                'password' => 'password',
                'rol' => 'admin',
                'ultimo_acceso' => now(),
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now(),
            ]);
        }
    }

}


