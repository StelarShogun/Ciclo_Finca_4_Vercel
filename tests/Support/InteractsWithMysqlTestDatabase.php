<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Guards feature tests that need the full MySQL schema (ENUM migrations, JSON_TABLE, etc.).
 *
 * Use with RefreshDatabase. Run via: composer test:mysql
 */
trait InteractsWithMysqlTestDatabase
{
    /**
     * @param  array<int, string>  $requiredTables
     */
    protected function skipUnlessMysqlTestDatabase(array $requiredTables = []): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'Este test requiere MySQL (esquema completo). Ejecuta: composer test:mysql o ./scripts/test-mysql-docker.sh'
            );
        }

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Falta la tabla `{$table}`. Ejecuta: composer test:mysql");
            }
        }
    }
}
