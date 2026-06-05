<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\Admin\ProductCatalog\Una\UnaCatalogImporter;
use App\Services\ProductClassificationAssignmentService;
use Illuminate\Database\Seeder;

/**
 * Importa TODAS las imágenes UNA desde /home/dilan/Documentos/UNA (18 ánforas + asientos en 5 carpetas).
 * Metadatos: nombre de archivo + catálogo visual (database/data/una_catalog.php).
 */
class UnaCatalogImportSeeder extends Seeder
{
    /** @var list<string> */
    private const OBSOLETE_PRODUCT_NAMES = [
        'Asiento Banana Ancho Favarcia',
        'Asiento Banana Favarcia',
        'Asiento MTB XRace DeePCOMF Gel Gaza',
        'Asiento MTB DDK Favarcia',
        'Asiento 20" Frees Comic Mix Gaza',
        'Asiento 20" Frees G Force Gaza',
        'Asiento 20" Frees Beast Camuflado Gaza',
        'Asiento 20" Frees Backflip Camuflado Beige Gaza',
        'Asiento 20" Frees Backflip Camuflado Gris Gaza',
    ];

    public function run(): void
    {
        $supplier = Supplier::query()->where('name', 'Accesorios Ciclismo Pro')->first()
            ?? Supplier::query()->where('status', 'active')->first();

        if (! $supplier) {
            $this->command->error('UnaCatalogImportSeeder: no hay proveedor activo.');

            return;
        }

        Product::query()->whereIn('name', self::OBSOLETE_PRODUCT_NAMES)->each(function (Product $p): void {
            $this->command->warn("  ↳ Eliminado obsoleto: {$p->name}");
            $p->delete();
        });

        $importer = new UnaCatalogImporter(
            app(ProductClassificationAssignmentService::class),
            $this->command,
        );

        $result = $importer->import($supplier);

        $pruned = $importer->pruneExtraSeats($result['seat_names'] ?? []);

        $this->command->info(sprintf(
            'UnaCatalogImportSeeder: %d producto(s) procesados, %d omitidos, %d asiento(s)/forro(s) sobrantes eliminados.',
            $result['processed'],
            $result['skipped'],
            $pruned,
        ));
    }
}
