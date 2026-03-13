<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('client_table')->insert([
            'name' => 'Darwin',
            'first_surname' => 'Nuñez',
            'second_surname' => 'Chavarría',
            'gmail' => 'darwinn990@gmail.com',
            'password' => Hash::make('Darwin1234$'),
            'remember_token' => bin2hex(random_bytes(10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}