<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    // Run the database seeds.
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Darwin',
                'first_surname' => 'Nuñez',
                'second_surname' => 'Chavarría',
                'gmail' => 'darwinn990@gmail.com',
                'password' => Hash::make('Darwin1234$'),
                'remember_token' => bin2hex(random_bytes(10)),
                'email_verified' => true,
                'active' => true,
                'provider' => 'local',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Arturo',
                'first_surname' => 'Chavarría',
                'second_surname' => null,
                'gmail' => 'itachi260704@gmail.com',
                'password' => Hash::make('12345678'),
                'remember_token' => bin2hex(random_bytes(10)),
                'email_verified' => true,
                'active' => true,
                'provider' => 'local',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($clients as $data) {
            $existing = DB::table('client_table')->where('gmail', $data['gmail'])->first();

            if ($existing) {
                $this->command->warn("El cliente {$data['gmail']} ya existe en la base de datos.");

                continue;
            }

            DB::table('client_table')->insert($data);
            $this->command->info("✅ Cliente creado: {$data['gmail']}");
        }
    }
}