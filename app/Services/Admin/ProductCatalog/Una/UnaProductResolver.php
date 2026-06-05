<?php

namespace App\Services\Admin\ProductCatalog\Una;

/**
 * Resuelve metadatos de producto UNA a partir de ruta relativa, nombre de archivo y carpeta.
 * Incluye overrides para fotos WhatsApp analizadas visualmente.
 */
class UnaProductResolver
{
    /** @var array<string, array<string, mixed>> */
    private static array $overrides = [];

    public static function boot(): void
    {
        if (self::$overrides !== []) {
            return;
        }

        $path = database_path('data/una_catalog.php');
        if (is_file($path)) {
            /** @var array<string, array<string, mixed>> $data */
            $data = require $path;
            self::$overrides = $data;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolve(string $collection, string $relativePath): ?array
    {
        self::boot();

        $key = $collection.'::'.str_replace('\\', '/', $relativePath);
        if (isset(self::$overrides[$key])) {
            return self::$overrides[$key];
        }

        $basename = basename($relativePath);
        $basenameKey = $collection.'::basename::'.$basename;
        if (isset(self::$overrides[$basenameKey])) {
            return self::$overrides[$basenameKey];
        }

        if ($collection === 'anforas') {
            return self::parseAnforaFilename($basename);
        }

        return self::parseAsientoPath($relativePath, $basename);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function parseAnforaFilename(string $basename): ?array
    {
        if (preg_match('/ANFORA ELITE FLY TEX (AZUL|GRIS|NEGRA|ROJA|MORADA)/i', $basename, $m)) {
            $color = self::normalizeColor($m[1]);

            return self::anforaEliteFlyTex($color);
        }

        if (stripos($basename, 'KEBEA SHIMANO') !== false) {
            return [
                'name' => 'Anfora Elite Kebea Shimano 550 ml — Azul',
                'brand' => ['Elite'],
                'category' => 'hidratacion',
                'description' => 'Anfora Elite Kebea edición Shimano, 550 ml. Diseño ergonómico con agarre antideslizante; compatible con portabidón estándar.',
                'purchase' => 4800,
                'sale' => 9500,
                'stock' => 14,
                'featured' => true,
                'classifications' => ['color' => 'Azul', 'capacidad' => '550 ml'],
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function anforaEliteFlyTex(string $color): array
    {
        return [
            'name' => 'Anfora Elite Fly Tex 550 ml — '.$color,
            'brand' => ['Elite'],
            'category' => 'hidratacion',
            'description' => 'Anfora deportiva Elite Fly Tex de 550 ml, ultraligera y flexible. Ideal para MTB y ruta; boquilla de fácil succión y cuerpo comprimible.',
            'purchase' => 4200,
            'sale' => 8900,
            'stock' => 16,
            'featured' => true,
            'classifications' => ['color' => $color, 'capacidad' => '550 ml'],
            'variant_group' => 'elite-fly-tex-550',
            'variant_color' => $color,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function parseAsientoPath(string $relativePath, string $basename): ?array
    {
        $path = str_replace('\\', '/', $relativePath);
        $upper = mb_strtoupper($basename.' '.$path);

        if (preg_match('/907350-(GRIS|ROJO)/i', $basename, $m)) {
            return self::bmoVariant('907350 MTB', $m[1], 'bmo-907350', 8500, 16500);
        }
        if (preg_match('/907352-(AZUL|NEGRO|ROJO|VERDE)/i', $basename, $m)) {
            return self::bmoVariant('907352 MTB', $m[1], 'bmo-907352', 9200, 17800);
        }
        if (preg_match('/900330(negro|rojo|verde)/i', $basename, $m)) {
            return self::bmoVariant('900330', $m[1], 'bmo-900330', 7800, 14900);
        }
        if (preg_match('/909408(azul|rojo|verde)/i', $basename, $m)) {
            return self::bmoVariant('909408', $m[1], 'bmo-909408', 9800, 18900);
        }
        if (preg_match('/FIFTYFIVE KUDDE GEL 230B (CELESTE|FUCSIA|GRIS)/i', $upper, $m)) {
            $color = self::normalizeColor($m[1]);

            return [
                'name' => 'Asiento MTB Fiftyfive Kudde Gel 230B — '.$color,
                'brand' => ['Fiftyfive'],
                'category' => 'asientos',
                'description' => 'Asiento MTB Fiftyfive Kudde Gel 230B, gel en zona isquios y carcasa flexible para trail.',
                'purchase' => 11000,
                'sale' => 21500,
                'stock' => 10,
                'classifications' => ['color' => $color, 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'variant_group' => 'fiftyfive-kudde-230b',
                'variant_color' => $color,
            ];
        }

        $named = [
            'ASIENTO 20 BANANA RESORTE SUPER PRO' => [
                'name' => 'Asiento 20" Banana Resorte Super Pro',
                'brand' => ['Banana'], 'purchase' => 6500, 'sale' => 12800,
                'classifications' => ['color' => 'Negro-Gris', 'size' => '20"', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana Resorte Super Pro para rodado 20", resorte trasero y base acolchada; ideal urbano y paseo.',
            ],
            'ASIENTO 26 BANANA BC 620A' => [
                'name' => 'Asiento 26" Banana BC 620A',
                'brand' => ['Banana'], 'purchase' => 7200, 'sale' => 13900,
                'classifications' => ['color' => 'Negro', 'size' => '26"', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana BC 620A para bicicleta 26", perfil clásico y costuras dobles.',
            ],
            'ASIENTO BANANA ANCHO' => [
                'name' => 'Asiento Banana Ancho',
                'brand' => ['Banana'], 'purchase' => 5800, 'sale' => 11200,
                'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana ancho con acolchado extra, recomendado para paseo y bicicletas de ciudad.',
            ],
            'ASIENTO BANANA-FAVARCIA' => [
                'name' => 'Asiento Banana estándar',
                'brand' => ['Banana'], 'purchase' => 5200, 'sale' => 9900,
                'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana estándar, confortable para uso recreativo y urbano.',
            ],
            'ASIENTO BANANA TÓTEM' => [
                'name' => 'Asiento Banana Tótem',
                'brand' => ['Banana'], 'purchase' => 5400, 'sale' => 10500,
                'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana diseño Tótem, acolchado medio y base resistente.',
            ],
            'ASIENTO TÓTEM RASTA' => [
                'name' => 'Asiento Banana Tótem Rasta',
                'brand' => ['Banana'], 'purchase' => 5600, 'sale' => 10900,
                'classifications' => ['color' => 'Verde', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento Banana Tótem edición Rasta, estilo urbano con acolchado cómodo.',
            ],
            'ASIENTO PLAYERO-NICOLÁS' => [
                'name' => 'Asiento Playero Nicolás',
                'brand' => ['Nicolás'], 'purchase' => 4800, 'sale' => 9200,
                'classifications' => ['color' => 'Azul', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento playero Nicolás, impermeable y de secado rápido para bici de paseo.',
            ],
            'ASIENTO MTB ALL TIME' => [
                'name' => 'Asiento MTB All Time ATV-8206A',
                'brand' => ['All Time'], 'purchase' => 9800, 'sale' => 19200,
                'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento MTB All Time ATV-8206A, gel en zona central y carcasa rígida para trail.',
            ],
            'ASIENTO MTB NEGRO GEL 275X158MM' => [
                'name' => 'Asiento MTB Gel All Time 275×158 mm',
                'brand' => ['All Time'], 'purchase' => 10200, 'sale' => 19800,
                'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento MTB All Time con gel, medidas 275×158 mm; alivio de presión en ruta y gravel.',
            ],
            'XRACE DEEPCOMF' => [
                'name' => 'Asiento MTB X-Race DeePCOMF Gel VL-4110',
                'brand' => ['X-Race'], 'purchase' => 11500, 'sale' => 22500,
                'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento MTB X-Race DeePCOMF con gel (VL-4110), canal central y acolchado confort para trail.',
            ],
            '900082' => [
                'name' => 'Asiento B-MO 100% Gel MTB',
                'brand' => ['B-MO'], 'purchase' => 12500, 'sale' => 24500,
                'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento B-MO 100% Gel para MTB, capa de gel completa y base de nylon reforzado.',
            ],
            '900333' => [
                'name' => 'Asiento B-MO Blanco MTB',
                'brand' => ['B-MO'], 'purchase' => 8800, 'sale' => 17200,
                'classifications' => ['color' => 'Blanco', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento B-MO MTB color blanco, espuma de alta densidad y rieles cromados.',
            ],
            'DDK NEGRO-BLANCO' => [
                'name' => 'Asiento MTB DDK Negro-Blanco',
                'brand' => ['DDK'], 'purchase' => 7600, 'sale' => 14600,
                'classifications' => ['color' => 'Negro-Blanco', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento MTB DDK bicolor negro/blanco, costuras contrastantes y base ABS.',
            ],
            'FORRO P-ASIENTO GEL' => [
                'name' => 'Forro de asiento gel acolchado BC Banana',
                'brand' => ['Banana'], 'category' => 'forros', 'purchase' => 3200, 'sale' => 6900,
                'classifications' => ['color' => 'Negro'],
                'description' => 'Forro de asiento con gel acolchado BC, mejora el confort sobre asientos existentes.',
            ],
            'FORRO ESPUMA' => [
                'name' => 'Forro de asiento espuma Banana',
                'brand' => ['Banana'], 'category' => 'forros', 'purchase' => 2800, 'sale' => 5900,
                'classifications' => ['color' => 'Negro'],
                'description' => 'Forro de asiento en espuma, ligero y transpirable para uso urbano.',
            ],
        ];

        foreach ($named as $needle => $def) {
            if (stripos($upper, $needle) !== false) {
                return self::seatDefaults($def);
            }
        }

        if ($basename === 'DDK.jpeg') {
            return self::seatDefaults([
                'name' => 'Asiento MTB DDK',
                'brand' => ['DDK'], 'purchase' => 7400, 'sale' => 14200,
                'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                'description' => 'Asiento MTB DDK con espuma dual y carcasa flexible para uso recreativo y trail ligero.',
            ]);
        }

        if (preg_match('/FREES (COMIC MIX|G FORCE|BEAST|BACKFLIP)/i', $upper, $m)) {
            return self::freesSeat($upper, $m[1]);
        }

        if (stripos($upper, 'BMX FREESTYLE') !== false) {
            return self::seatDefaults([
                'name' => 'Asiento BMX Freestyle Nicolás RX-1612',
                'brand' => ['Nicolás'], 'purchase' => 5100, 'sale' => 9800,
                'classifications' => ['color' => 'Negro', 'size' => 'BMX', 'tipo-uso' => 'Infantil / BMX'],
                'description' => 'Asiento BMX freestyle Nicolás RX-1612, acolchado suave para tricks y street.',
            ]);
        }

        if (stripos($upper, 'NIÑO HOMBRE ARAÑA') !== false) {
            return self::seatDefaults([
                'name' => 'Asiento Niño Hombre Araña Nicolás',
                'brand' => ['Nicolás'], 'purchase' => 3900, 'sale' => 7800,
                'classifications' => ['color' => 'Rojo', 'size' => '20"', 'tipo-uso' => 'Infantil / BMX'],
                'description' => 'Asiento infantil diseño Araña, divertido y cómodo para bicicletas de niño.',
            ]);
        }

        if (stripos($path, '/BMO/') !== false || stripos($upper, 'B-MO') !== false) {
            if (stripos($upper, 'AMARILLO') !== false) {
                return self::bmoStandalone('Amarillo MTB', 'Amarillo', 8200, 15800);
            }
            if (stripos($upper, 'PROSTATIC') !== false || stripos($upper, 'PROSTÁTICO') !== false) {
                return self::bmoStandalone('Prostático MTB', 'Negro', 9600, 18500);
            }
        }

        if (preg_match('#^(ASIENTOS|INFANTILES Y BMX|MTB)/([^/]+)/#', $path, $m) && self::looksLikeGenericPhoto($basename)) {
            return self::resolveAsientoSubfolder($m[1], $m[2]);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function bmoVariant(string $model, string $colorRaw, string $group, int $purchase, int $sale): array
    {
        $color = self::normalizeColor($colorRaw);

        return [
            'name' => 'Asiento B-MO '.$model.' — '.$color,
            'brand' => ['B-MO'],
            'category' => 'asientos',
            'description' => 'Asiento B-MO modelo '.$model.', espuma confort y base reforzada para MTB y urbano.',
            'purchase' => $purchase,
            'sale' => $sale,
            'stock' => 12,
            'classifications' => ['color' => $color, 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
            'variant_group' => $group,
            'variant_color' => $color,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function bmoStandalone(string $suffix, string $color, int $purchase, int $sale): array
    {
        return self::seatDefaults([
            'name' => 'Asiento B-MO '.$suffix,
            'brand' => ['B-MO'], 'purchase' => $purchase, 'sale' => $sale,
            'classifications' => ['color' => $color, 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
            'description' => 'Asiento B-MO '.$suffix.', acolchado medio y base resistente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function freesSeat(string $upper, string $line): array
    {
        $color = 'Camuflado';
        if (stripos($upper, 'NEGRO-GRIS') !== false) {
            $color = 'Negro-Gris';
        } elseif (stripos($upper, 'BEIGE') !== false) {
            $color = 'Beige';
        } elseif (stripos($upper, 'GRIS') !== false && stripos($upper, 'CAMUFLADO') !== false) {
            $color = 'Gris';
        } elseif (stripos($upper, 'COMIC') !== false) {
            $color = 'Camuflado';
        }

        $label = trim(str_replace(['ASIENTO 20', 'FREES', 'C-GAZA', 'C/GAZA', 'BR01220-022', 'BR01220-051', 'BR01220-052'], '', $upper));
        $label = preg_replace('/\s+/', ' ', $label) ?: $line;

        return self::seatDefaults([
            'name' => 'Asiento 20" Frees '.ucwords(strtolower($line)),
            'brand' => ['Frees'], 'purchase' => 4500, 'sale' => 9000,
            'classifications' => ['color' => $color, 'size' => '20"', 'tipo-uso' => 'Infantil / BMX'],
            'description' => 'Asiento infantil 20" línea Frees '.$line.', acolchado cómodo con abrazadera de tija.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array<string, mixed>
     */
    private static function seatDefaults(array $def): array
    {
        return array_merge([
            'category' => 'asientos',
            'stock' => 10,
            'featured' => false,
            'classifications' => [],
        ], $def);
    }

    /**
     * Un producto por subcarpeta (BMO, DIST86, FAVARCIA, NICOLÁS) cuando solo se importa la foto representativa.
     *
     * @return array<string, mixed>
     */
    private static function resolveAsientoSubfolder(string $group, string $subfolder): array
    {
        $sub = mb_strtoupper($subfolder);

        if ($group === 'ASIENTOS') {
            return match (true) {
                str_contains($sub, 'BANANA') => self::seatDefaults([
                    'name' => 'Asiento Banana — surtido urbano (ASIENTOS)',
                    'brand' => ['Banana'], 'purchase' => 5500, 'sale' => 10800,
                    'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                    'description' => 'Línea Banana en carpeta ASIENTOS: Resorte Super Pro 20", BC 620A 26", ancho, tótem y estándar.',
                ]),
                str_contains($sub, 'FORRO') => self::seatDefaults([
                    'name' => 'Forro de asiento gel acolchado BC Banana',
                    'brand' => ['Banana'], 'category' => 'forros', 'purchase' => 3000, 'sale' => 6500,
                    'classifications' => ['color' => 'Negro'],
                    'description' => 'Forro BA01200 gel acolchado BC para mayor confort sobre el asiento.',
                ]),
                str_contains($sub, 'INFANTIL') => self::seatDefaults([
                    'name' => 'Asiento Nicolás Niño Araña — infantil',
                    'brand' => ['Nicolás'], 'purchase' => 3900, 'sale' => 7800,
                    'classifications' => ['color' => 'Rojo', 'size' => '20"', 'tipo-uso' => 'Infantil / BMX'],
                    'description' => 'Asiento infantil diseño Araña de la carpeta INFANTILES (ASIENTOS).',
                ]),
                str_contains($sub, 'MTB') => self::seatDefaults([
                    'name' => 'Asiento MTB All Time ATV-8206A — Negro',
                    'brand' => ['All Time'], 'purchase' => 9500, 'sale' => 18500,
                    'classifications' => ['color' => 'Negro', 'size' => 'MTB universal', 'tipo-uso' => 'MTB'],
                    'description' => 'Asiento MTB All Time ATV-8206A con gel; línea trail de carpeta MTB (ASIENTOS).',
                ]),
                default => self::seatDefaults([
                    'name' => 'Asiento '.$subfolder,
                    'brand' => ['Banana'], 'purchase' => 5000, 'sale' => 9900,
                    'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                    'description' => 'Asiento importado desde carpeta ASIENTOS/'.$subfolder.'.',
                ]),
            };
        }

        return match ($sub) {
            'BMO' => self::seatDefaults([
                'name' => $group === 'INFANTILES Y BMX'
                    ? 'Asiento Infantil B-MO Gato Futbolista — surtido'
                    : 'Asiento B-MO 100% Gel MTB — Negro',
                'brand' => ['B-MO'], 'purchase' => $group === 'INFANTILES Y BMX' ? 5200 : 12500,
                'sale' => $group === 'INFANTILES Y BMX' ? 10200 : 24500,
                'classifications' => [
                    'color' => $group === 'INFANTILES Y BMX' ? 'Rojo' : 'Negro',
                    'size' => $group === 'INFANTILES Y BMX' ? '20"' : 'MTB universal',
                    'tipo-uso' => $group === 'INFANTILES Y BMX' ? 'Infantil / BMX' : 'MTB',
                ],
                'description' => $group === 'INFANTILES Y BMX'
                    ? 'Línea infantil B-MO (carpeta BMO): modelos Gato Futbolista y similares.'
                    : 'Línea B-MO MTB con gel 100% (modelo 900082 y variantes en carpeta BMO).',
            ]),
            'DIST86' => self::seatDefaults([
                'name' => $group === 'INFANTILES Y BMX'
                    ? 'Asiento 20" Frees — surtido Dist86'
                    : 'Asiento MTB Fiftyfive Kudde Gel 230B — surtido',
                'brand' => $group === 'INFANTILES Y BMX' ? ['Frees'] : ['Fiftyfive'],
                'purchase' => $group === 'INFANTILES Y BMX' ? 4500 : 11000,
                'sale' => $group === 'INFANTILES Y BMX' ? 9000 : 21500,
                'classifications' => [
                    'color' => $group === 'INFANTILES Y BMX' ? 'Camuflado' : 'Negro',
                    'size' => $group === 'INFANTILES Y BMX' ? '20"' : 'MTB universal',
                    'tipo-uso' => $group === 'INFANTILES Y BMX' ? 'Infantil / BMX' : 'MTB',
                ],
                'description' => $group === 'INFANTILES Y BMX'
                    ? 'Surtido infantil Frees Comic Mix, G Force y Beast (carpeta DIST86).'
                    : 'Surtido MTB Fiftyfive Kudde Gel 230B en celeste, fucsia y gris (DIST86).',
            ]),
            'FAVARCIA' => self::seatDefaults([
                'name' => $group === 'INFANTILES Y BMX'
                    ? 'Asiento infantil Favarcia — surtido WhatsApp'
                    : 'Asiento MTB DDK — surtido Favarcia',
                'brand' => $group === 'INFANTILES Y BMX' ? ['Favarcia'] : ['DDK'],
                'purchase' => 6800, 'sale' => 13200,
                'classifications' => [
                    'color' => 'Negro',
                    'size' => $group === 'INFANTILES Y BMX' ? '20"' : 'MTB universal',
                    'tipo-uso' => $group === 'INFANTILES Y BMX' ? 'Infantil / BMX' : 'MTB',
                ],
                'description' => 'Asiento línea Favarcia importado desde carpeta del proveedor; ver variantes en catálogo físico.',
            ]),
            'NICOLÁS', 'NICOLAS' => self::seatDefaults([
                'name' => $group === 'INFANTILES Y BMX'
                    ? 'Asiento BMX Freestyle Nicolás RX-1612'
                    : 'Asiento MTB All Time Gel — Negro',
                'brand' => ['Nicolás'], 'purchase' => $group === 'INFANTILES Y BMX' ? 5100 : 10200,
                'sale' => $group === 'INFANTILES Y BMX' ? 9800 : 19800,
                'classifications' => [
                    'color' => 'Negro',
                    'size' => $group === 'INFANTILES Y BMX' ? '20"' : 'MTB universal',
                    'tipo-uso' => $group === 'INFANTILES Y BMX' ? 'Infantil / BMX' : 'Urbano',
                ],
                'description' => 'Asiento Nicolás importado desde carpeta del proveedor (línea infantil o urbana según grupo).',
            ]),
            default => self::seatDefaults([
                'name' => 'Asiento '.$subfolder,
                'brand' => ['Saddle Bike'], 'purchase' => 5000, 'sale' => 9900,
                'classifications' => ['color' => 'Negro', 'tipo-uso' => 'Urbano'],
                'description' => 'Asiento importado desde carpeta '.$subfolder.' ('.$group.').',
            ]),
        };
    }

    private static function looksLikeGenericPhoto(string $basename): bool
    {
        $n = mb_strtolower($basename);

        return str_contains($n, 'whatsapp')
            || str_contains($n, 'imagen de whatsapp')
            || preg_match('/^img_\d+/i', $basename) === 1;
    }

    private static function normalizeColor(string $raw): string
    {
        $c = mb_strtoupper(trim($raw));

        return match ($c) {
            'NEGRA', 'NEGRO' => 'Negro',
            'ROJA', 'ROJO' => 'Rojo',
            'AZUL' => 'Azul',
            'VERDE' => 'Verde',
            'GRIS' => 'Gris',
            'MORADA', 'MORADO' => 'Morado',
            'BLANCO' => 'Blanco',
            'AMARILLO' => 'Amarillo',
            'CELESTE' => 'Celeste',
            'FUCSIA' => 'Fucsia',
            'BEIGE' => 'Beige',
            default => ucfirst(mb_strtolower($raw)),
        };
    }
}
