<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * IDs públicos (ULID) para todo lo que aparece en URLs del cliente.
 * Los autoincrementales quedan como claves internas; el SPA nunca los ve.
 */
return new class extends Migration
{
    /** tabla => clave primaria */
    private const TABLES = [
        'products' => 'product_id',
        'categories' => 'category_id',
        'brands' => 'id',
        'sales' => 'sale_id',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table => $pk) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->char('public_id', 26)->nullable()->unique("{$table}_public_id_unique");
            });

            DB::table($table)->whereNull('public_id')->orderBy($pk)
                ->chunkById(500, function ($rows) use ($table, $pk): void {
                    foreach ($rows as $row) {
                        DB::table($table)->where($pk, $row->{$pk})
                            ->update(['public_id' => (string) Str::ulid()]);
                    }
                }, $pk);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique("{$table}_public_id_unique");
                $blueprint->dropColumn('public_id');
            });
        }
    }
};
