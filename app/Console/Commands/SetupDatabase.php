<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupDatabase extends Command
{
    protected $signature = 'db:setup';
    protected $description = 'Configura la base de datos ejecutando migraciones y seeders';

    public function handle()
    {
        $this->info('🚀 Configurando la base de datos...');

        try {
            // Ejecutar migraciones
            $this->info('📊 Ejecutando migraciones...');
            Artisan::call('migrate:fresh');
            $this->info('✅ Migraciones completadas');

            // Ejecutar seeders
            $this->info('🌱 Ejecutando seeders...');
            Artisan::call('db:seed');
            $this->info('✅ Seeders completados');

            $this->info('🎉 Base de datos configurada exitosamente');
            $this->info('📝 Usuarios creados:');
            $this->info('   - admin@example.com (password: password)');
            $this->info('   - juan@example.com (password: password)');
            $this->info('   - maria@example.com (password: password)');

        } catch (\Exception $e) {
            $this->error('❌ Error al configurar la base de datos: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
