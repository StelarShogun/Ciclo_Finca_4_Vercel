<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupDatabase extends Command
{
    protected $signature = 'dev:setup-database';

    protected $description = 'Fresh migrate and seed (local/testing only)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Este comando solo puede ejecutarse en local/testing.');

            return self::FAILURE;
        }

        $this->info('🚀 Configurando la base de datos...');

        try {
            $this->info('📊 Ejecutando migraciones...');
            Artisan::call('migrate:fresh');
            $this->info('✅ Migraciones completadas');

            $this->info('🌱 Ejecutando seeders...');
            Artisan::call('db:seed');
            $this->info('✅ Seeders completados');

            $this->info('🎉 Base de datos configurada exitosamente');
            $this->info('📝 Usuarios creados:');
            $this->info('   - admin@example.com (password: password)');
            $this->info('   - juan@example.com (password: password)');
            $this->info('   - maria@example.com (password: password)');
        } catch (\Exception $e) {
            $this->error('❌ Error al configurar la base de datos: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
