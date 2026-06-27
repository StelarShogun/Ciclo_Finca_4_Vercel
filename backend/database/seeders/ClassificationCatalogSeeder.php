<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use Illuminate\Database\Seeder;

/**
 * Por subcategoría: atributos (Color, Marca…) y valores (Rojo, Kenda…).
 * Un valor por atributo por producto (tablas classification_*).
 */
class ClassificationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $subcategories = Category::query()
            ->whereNotNull('parent_category_id')
            ->orderBy('category_id')
            ->get();

        if ($subcategories->isEmpty()) {
            return;
        }

        $genericDimensions = [
            [
                'slug' => 'color',
                'label' => 'Color',
                'sort_order' => 0,
                'values' => ['Rojo', 'Azul', 'Negro'],
            ],
            [
                'slug' => 'size',
                'label' => 'Talla',
                'sort_order' => 1,
                'values' => ['S', 'M', 'L'],
            ],
        ];

        /** Ejemplo tipo “neumático Kenda mountainbike #4”: atributos separados, no un solo texto largo. */
        $ruedasNeumaticosDimensions = [
            [
                'slug' => 'marca',
                'label' => 'Marca',
                'sort_order' => 0,
                'values' => ['Kenda', 'Schwalbe', 'Michelin'],
            ],
            [
                'slug' => 'uso',
                'label' => 'Uso o tipo',
                'sort_order' => 1,
                'values' => ['Mountainbike', 'BMX', 'Ruta', 'Urbano'],
            ],
            [
                'slug' => 'medida',
                'label' => 'Medida / tamaño',
                'sort_order' => 2,
                'values' => ['#4', '26"', '29"', '700×25C'],
            ],
            [
                'slug' => 'color',
                'label' => 'Color',
                'sort_order' => 3,
                'values' => ['Rojo', 'Negro', 'Azul'],
            ],
        ];

        foreach ($subcategories as $sub) {
            $defs = $sub->name === 'Ruedas y neumáticos'
                ? $ruedasNeumaticosDimensions
                : $genericDimensions;

            foreach ($defs as $def) {
                $dim = ClassificationDimension::query()->firstOrCreate(
                    [
                        'category_id' => $sub->category_id,
                        'slug' => $def['slug'],
                    ],
                    [
                        'label' => $def['label'],
                        'sort_order' => $def['sort_order'],
                    ]
                );

                foreach ($def['values'] as $i => $valueLabel) {
                    $norm = ClassificationValue::normalizeStoredValue($valueLabel);
                    ClassificationValue::query()->firstOrCreate(
                        [
                            'classification_dimension_id' => $dim->id,
                            'normalized_value' => $norm,
                        ],
                        [
                            'value' => $valueLabel,
                            'sort_order' => $i,
                        ]
                    );
                }
            }
        }
    }
}
