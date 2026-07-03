<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Demo brands for admin /marca and client catalog filters.
 */
class BrandSeeder extends Seeder
{
    /** @var list<string> */
    private const DEMO_BRANDS = [
        'Trek',
        'Giant',
        'Cannondale',
        'Specialized',
        'Shimano',
        'SRAM',
        'Cateye',
        'Castelli',
        'Pearl Izumi',
        'Gore',
        'Fox',
        'Giro',
        'Park Tool',
        'Topeak',
        'Elite',
        'Knog',
        'Maxxis',
        'Kryptonite',
        'GU Energy',
        'Powerade',
        'Clif Bar',
        'Marca demo Ciclo Finca',
        'B-MO',
        'Banana',
        'All Time',
        'Nicolás',
        'Fiftyfive',
        'DDK',
        'X-Race',
        'Frees',
        'La Bici',
        'Lujo',
        'Specialized',
        'BC',
        'Force',
        'Saddle Bike',
    ];

    public function run(): void
    {
        $created = 0;
        $existing = 0;

        foreach (self::DEMO_BRANDS as $name) {
            $brand = Brand::query()->firstOrCreate(['name' => $name]);
            if ($brand->wasRecentlyCreated) {
                $created++;
            } else {
                $existing++;
            }
        }

        $this->command->info("BrandSeeder: {$created} marca(s) creada(s), {$existing} ya existían.");
    }
}
