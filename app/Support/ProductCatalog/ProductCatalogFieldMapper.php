<?php

namespace App\Support\ProductCatalog;

/**
 * Maps heterogeneous supplier rows (CSV/XML/JSON keys) to canonical product fields.
 */
final class ProductCatalogFieldMapper
{
    /** @var array<string, list<string>> */
    private const ALIASES = [
        'name' => ['name', 'nombre', 'producto', 'product_name', 'producto_nombre', 'titulo', 'title', 'descripcion_corta', 'item_name', 'articulo'],
        'description' => ['description', 'descripcion', 'detalle', 'details', 'observaciones', 'notas'],
        'sku' => ['sku', 'codigo', 'codigo_producto', 'code', 'product_code', 'referencia', 'ref', 'barcode', 'ean', 'upc', 'id_producto'],
        'product_id' => ['product_id', 'id', 'productid', 'internal_id'],
        'category' => ['category', 'categoria', 'category_name', 'categoria_nombre', 'tipo', 'subcategoria', 'subcategory', 'subcategory_name', 'familia'],
        'parent_category' => ['parent_category', 'categoria_padre', 'parent_category_name', 'categoria_principal', 'linea'],
        'supplier' => ['supplier', 'proveedor', 'supplier_name', 'proveedor_nombre', 'vendor'],
        'brand' => ['brand', 'marca', 'brand_name', 'marca_nombre', 'brands', 'marcas'],
        'purchase_price' => ['purchase_price', 'precio_compra', 'costo', 'cost', 'precio_costo', 'purchase', 'cost_price', 'precio_proveedor'],
        'sale_price' => ['sale_price', 'precio_venta', 'precio', 'price', 'pvp', 'precio_publico', 'sale', 'retail_price', 'precio_final'],
        'stock_current' => ['stock_current', 'stock', 'stock_actual', 'cantidad', 'quantity', 'qty', 'inventario', 'existencia', 'stock_disponible'],
        'stock_minimum' => ['stock_minimum', 'stock_minimo', 'min_stock', 'stock_min', 'minimum_stock'],
        'status' => ['status', 'estado', 'state', 'disponibilidad'],
        'is_featured' => ['is_featured', 'destacado', 'featured', 'destacado_tienda'],
    ];

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function normalizeRow(array $row): array
    {
        $flat = self::flattenRow($row);
        $out = [];

        foreach (self::ALIASES as $canonical => $aliases) {
            $value = self::pick($flat, $aliases);
            if ($value !== null && $value !== '') {
                $out[$canonical] = $value;
            }
        }

        foreach ($flat as $key => $value) {
            if (! is_string($key) || $value === null || $value === '') {
                continue;
            }
            if (preg_match('/^(attr_|classification_|atributo_)(.+)$/i', $key, $m)) {
                $slug = self::slugKey($m[2]);
                $out['classifications'][$slug] = is_scalar($value) ? trim((string) $value) : $value;

                continue;
            }
            if (in_array(self::slugKey($key), ['color', 'talla', 'size', 'capacidad', 'tipo_uso', 'tipo-uso'], true)) {
                $out['classifications'][self::slugKey($key)] = trim((string) $value);
            }
        }

        if (isset($out['brand']) && is_string($out['brand'])) {
            $out['brands'] = array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $out['brand']) ?: [])));
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public static function rowLooksLikeProduct(array $row): bool
    {
        $n = self::normalizeRow($row);

        return isset($n['name']) && trim((string) $n['name']) !== ''
            || isset($n['sku']) && trim((string) $n['sku']) !== ''
            || (isset($n['sale_price']) || isset($n['purchase_price']));
    }

    /**
     * @param  array<string, mixed>  $flat
     * @param  list<string>  $aliases
     */
    private static function pick(array $flat, array $aliases): mixed
    {
        foreach ($aliases as $alias) {
            $key = self::slugKey($alias);
            if (array_key_exists($key, $flat)) {
                $v = $flat[$key];
                if ($v !== null && $v !== '') {
                    return is_string($v) ? trim($v) : $v;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function flattenRow(array $row): array
    {
        $out = [];
        foreach ($row as $key => $value) {
            $k = self::slugKey((string) $key);
            if (is_array($value) && self::isAssoc($value)) {
                foreach (self::flattenRow($value) as $subK => $subV) {
                    $out[$subK] = $subV;
                }
            } elseif (is_scalar($value) || $value === null) {
                $out[$k] = $value;
            }
        }

        return $out;
    }

    private static function slugKey(string $key): string
    {
        $s = mb_strtolower(trim($key));
        $s = str_replace([' ', '-', '.'], '_', $s);
        $s = preg_replace('/[^a-z0-9_]/u', '', $s) ?? $s;

        return $s;
    }

    /**
     * @param  array<mixed>  $arr
     */
    private static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
