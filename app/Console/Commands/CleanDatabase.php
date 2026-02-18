<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia la base de datos eliminando productos de relleno y datos de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de la base de datos...');

        try {
            DB::beginTransaction();

            // Eliminar productos de relleno (que tengan nombres genéricos o de prueba)
            $productosEliminados = Producto::where(function($query) {
                $query->where('nombre', 'like', '%test%')
                      ->orWhere('nombre', 'like', '%prueba%')
                      ->orWhere('nombre', 'like', '%sample%')
                      ->orWhere('nombre', 'like', '%demo%')
                      ->orWhere('nombre', 'like', '%lorem%')
                      ->orWhere('nombre', 'like', '%ipsum%')
                      ->orWhere('nombre', 'like', '%dolor%')
                      ->orWhere('nombre', 'like', '%sit%')
                      ->orWhere('nombre', 'like', '%amet%')
                      ->orWhere('nombre', 'like', '%consectetur%')
                      ->orWhere('nombre', 'like', '%adipiscing%')
                      ->orWhere('nombre', 'like', '%elit%')
                      ->orWhere('nombre', 'like', '%sed%')
                      ->orWhere('nombre', 'like', '%do%')
                      ->orWhere('nombre', 'like', '%eiusmod%')
                      ->orWhere('nombre', 'like', '%tempor%')
                      ->orWhere('nombre', 'like', '%incididunt%')
                      ->orWhere('nombre', 'like', '%labore%')
                      ->orWhere('nombre', 'like', '%dolore%')
                      ->orWhere('nombre', 'like', '%magna%')
                      ->orWhere('nombre', 'like', '%aliqua%')
                      ->orWhere('nombre', 'like', '%ut%')
                      ->orWhere('nombre', 'like', '%enim%')
                      ->orWhere('nombre', 'like', '%ad%')
                      ->orWhere('nombre', 'like', '%minim%')
                      ->orWhere('nombre', 'like', '%veniam%')
                      ->orWhere('nombre', 'like', '%quis%')
                      ->orWhere('nombre', 'like', '%nostrud%')
                      ->orWhere('nombre', 'like', '%exercitation%')
                      ->orWhere('nombre', 'like', '%ullamco%')
                      ->orWhere('nombre', 'like', '%laboris%')
                      ->orWhere('nombre', 'like', '%nisi%')
                      ->orWhere('nombre', 'like', '%aliquip%')
                      ->orWhere('nombre', 'like', '%ex%')
                      ->orWhere('nombre', 'like', '%ea%')
                      ->orWhere('nombre', 'like', '%commodo%')
                      ->orWhere('nombre', 'like', '%consequat%')
                      ->orWhere('nombre', 'like', '%duis%')
                      ->orWhere('nombre', 'like', '%aute%')
                      ->orWhere('nombre', 'like', '%irure%')
                      ->orWhere('nombre', 'like', '%reprehenderit%')
                      ->orWhere('nombre', 'like', '%voluptate%')
                      ->orWhere('nombre', 'like', '%velit%')
                      ->orWhere('nombre', 'like', '%esse%')
                      ->orWhere('nombre', 'like', '%cillum%')
                      ->orWhere('nombre', 'like', '%fugiat%')
                      ->orWhere('nombre', 'like', '%nulla%')
                      ->orWhere('nombre', 'like', '%pariatur%')
                      ->orWhere('nombre', 'like', '%excepteur%')
                      ->orWhere('nombre', 'like', '%sint%')
                      ->orWhere('nombre', 'like', '%occaecat%')
                      ->orWhere('nombre', 'like', '%cupidatat%')
                      ->orWhere('nombre', 'like', '%non%')
                      ->orWhere('nombre', 'like', '%proident%')
                      ->orWhere('nombre', 'like', '%sunt%')
                      ->orWhere('nombre', 'like', '%culpa%')
                      ->orWhere('nombre', 'like', '%qui%')
                      ->orWhere('nombre', 'like', '%officia%')
                      ->orWhere('nombre', 'like', '%deserunt%')
                      ->orWhere('nombre', 'like', '%mollit%')
                      ->orWhere('nombre', 'like', '%anim%')
                      ->orWhere('nombre', 'like', '%id%')
                      ->orWhere('nombre', 'like', '%est%')
                      ->orWhere('nombre', 'like', '%laborum%');
            })->delete();

            $this->info("✅ Eliminados {$productosEliminados} productos de relleno");

            // Limpiar tablas relacionadas que puedan tener datos de prueba
            try {
                $historialEliminado = DB::table('historial_inventario')->where('motivo', 'like', '%test%')->delete();
                $this->info("✅ Eliminados {$historialEliminado} registros de historial de prueba");
            } catch (\Exception $e) {
                $this->warn("⚠️  Tabla historial_inventario no encontrada, continuando...");
            }

            // Verificar que las categorías y proveedores principales estén intactos
            $categoriasCount = Categoria::count();
            $proveedoresCount = Proveedor::count();
            
            $this->info("📊 Estado actual de la base de datos:");
            $this->info("   - Categorías: {$categoriasCount}");
            $this->info("   - Proveedores: {$proveedoresCount}");
            $this->info("   - Productos restantes: " . Producto::count());

            DB::commit();
            $this->info('🎉 Limpieza completada exitosamente');

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error durante la limpieza: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}