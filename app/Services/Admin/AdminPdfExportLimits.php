<?php

namespace App\Services\Admin;

final class AdminPdfExportLimits
{
    public const INVENTORY_MAX_ROWS = 500;

    public const SALES_MAX_ROWS = 300;

    public const PRODUCT_SALES_TABLE_MAX_ROWS = 200;

    /** Listados de catálogo / pedidos en exportaciones de reportes (proveedores, marcas, etc.). */
    public const REGISTRY_MAX_ROWS = 500;

    /** Tamaño de chunk al escribir CSV de registro (memoria estable con muchas filas). */
    public const REGISTRY_CSV_CHUNK = 200;

    /** Tamaño de chunk al exportar ventas a CSV. */
    public const SALES_CSV_CHUNK = 200;

    /** Tamaño de chunk al exportar inventario a CSV. */
    public const INVENTORY_CSV_CHUNK = 200;
}
