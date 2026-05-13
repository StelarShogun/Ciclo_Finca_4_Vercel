<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPurchasePriceHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * XmlPriceDeviationService
 *
 * Responsible for:
 *  1. Parsing a supplier XML file and extracting product lines.
 *  2. Matching each XML line to a Product record in the database.
 *  3. Calculating the monetary and percentage deviation against
 *     products.purchase_price.
 *  4. Suggesting a new sale_price (preserving current margin) when
 *     the XML price is higher than the current purchase_price.
 *  5. Persisting confirmed price updates and writing history records.
 *
 * ── Expected XML structure ───────────────────────────────────────────────────
 *
 * The service is intentionally flexible. It accepts any of the common
 * supplier XML layouts below. Add more variants in resolveItem() as needed.
 *
 * Variant A – generic <items><item>…</item></items>:
 * <items>
 *   <item>
 *     <code>ACT-001</code>
 *     <name>Aceite 10W-40</name>
 *     <quantity>10</quantity>
 *     <unit_price>11200.00</unit_price>   <!-- or <purchase_price> -->
 *   </item>
 * </items>
 *
 * Variant B – <products><product>…</product></products>:
 * <products>
 *   <product>
 *     <sku>ACT-001</sku>
 *     <description>Aceite 10W-40</description>
 *     <qty>10</qty>
 *     <price>11200.00</price>
 *   </product>
 * </products>
 *
 * Variant C – <invoice><line>…</line></invoice> (common in Costa Rica):
 * <invoice>
 *   <line>
 *     <CodigoComercial>ACT-001</CodigoComercial>
 *     <Detalle>Aceite 10W-40</Detalle>
 *     <Cantidad>10</Cantidad>
 *     <PrecioUnitario>11200.00</PrecioUnitario>
 *   </line>
 * </invoice>
 * ────────────────────────────────────────────────────────────────────────────
 */
class XmlPriceDeviationService
{
    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Parse the uploaded XML and return an analysis array.
     *
     * Each item now includes sale-price suggestion fields when the XML price
     * is higher than the current purchase price:
     *
     *   current_sale_price       — current products.sale_price
     *   current_margin_pct       — (sale_price - purchase_price) / purchase_price * 100
     *   suggested_sale_price     — sale_price that keeps the same margin with the new cost
     *                              null when xml_price <= current_price (no increase)
     *   sale_price_increase      — suggested_sale_price - current_sale_price
     */
    public function analyse(UploadedFile $file, float $thresholdPct = 10.0): array
    {
        $xml = $this->parseXml($file);

        $rawItems = $this->extractItems($xml);

        $result = [];

        foreach ($rawItems as $raw) {
            $sku = (string) ($raw['code'] ?? '');
            $name = (string) ($raw['name'] ?? '');
            $quantity = (float) ($raw['quantity'] ?? 0);
            $xmlPrice = (float) ($raw['price'] ?? 0);

            $product = $this->findProduct($sku);

            if ($product) {
                $currentPrice = (float) $product->purchase_price;
                $currentSalePrice = (float) $product->sale_price;

                $differenceAmount = round($xmlPrice - $currentPrice, 2);
                $differencePct = $currentPrice > 0
                    ? round((($xmlPrice - $currentPrice) / $currentPrice) * 100, 4)
                    : 100.0;
                $hasDeviation = abs($differencePct) >= $thresholdPct;

                // ── Sale price suggestion ──────────────────────────────────
                // Only suggest when the purchase price is actually going UP.
                $suggestedSalePrice = null;
                $salePriceIncrease = null;
                $currentMarginPct = 0.0;

                if ($xmlPrice > $currentPrice) {
                    // Current margin percentage (markup over cost).
                    $currentMarginPct = $currentPrice > 0
                        ? round((($currentSalePrice - $currentPrice) / $currentPrice) * 100, 4)
                        : 0.0;

                    // Apply the same markup to the new cost.
                    $suggestedSalePrice = round($xmlPrice * (1 + $currentMarginPct / 100), 2);
                    $salePriceIncrease = round($suggestedSalePrice - $currentSalePrice, 2);
                }
                // ──────────────────────────────────────────────────────────

                $result[] = [
                    'product_id' => (int) $product->product_id,
                    'sku' => $product->displaySku(),
                    'name' => (string) $product->name,
                    'quantity' => $quantity,
                    'xml_price' => $xmlPrice,
                    'current_price' => $currentPrice,
                    'difference_amount' => $differenceAmount,
                    'difference_percentage' => $differencePct,
                    'has_deviation' => $hasDeviation,
                    'found' => true,
                    // Sale price fields:
                    'current_sale_price' => $currentSalePrice,
                    'current_margin_pct' => $currentMarginPct,
                    'suggested_sale_price' => $suggestedSalePrice,
                    'sale_price_increase' => $salePriceIncrease,
                ];
            } else {
                // Product not found in DB – include as unmatched row.
                $result[] = [
                    'product_id' => null,
                    'sku' => $sku,
                    'name' => $name,
                    'quantity' => $quantity,
                    'xml_price' => $xmlPrice,
                    'current_price' => 0.0,
                    'difference_amount' => $xmlPrice,
                    'difference_percentage' => 100.0,
                    'has_deviation' => true,
                    'found' => false,
                    // Sale price fields (empty for unmatched products):
                    'current_sale_price' => 0.0,
                    'current_margin_pct' => 0.0,
                    'suggested_sale_price' => null,
                    'sale_price_increase' => null,
                ];
            }
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'threshold_percentage' => $thresholdPct,
            'items' => $result,
        ];
    }

    /**
     * Persist the price updates that the admin confirmed.
     *
     * @param  array  $updates  Items from analysis filtered to those selected.
     *                          Each entry must contain: product_id, xml_price,
     *                          current_price, difference_amount, difference_percentage.
     * @param  string|null  $reason  Optional admin note.
     * @param  int  $changedBy  Admin user_id.
     * @param  array  $salePrices  Map of product_id => new_sale_price entered by the
     *                             admin in the review form. A null / missing entry means
     *                             "do not touch sale_price for this product".
     *                             Example: [42 => 15800.00, 99 => 22000.00]
     * @return int Number of products whose purchase_price was updated.
     */
    public function applyUpdates(
        array $updates,
        float $thresholdPct,
        string $xmlFileName,
        ?string $reason,
        int $changedBy,
        array $salePrices = [],   // ← new parameter
    ): int {
        $count = 0;

        DB::transaction(function () use (
            $updates, $thresholdPct, $xmlFileName, $reason, $changedBy, $salePrices, &$count
        ) {
            foreach ($updates as $item) {
                $productId = (int) ($item['product_id'] ?? 0);

                if ($productId <= 0) {
                    continue;
                }

                $product = Product::find($productId);

                if (! $product) {
                    Log::warning('XmlPriceDeviationService: product not found during applyUpdates', [
                        'product_id' => $productId,
                    ]);

                    continue;
                }

                $previousPrice = (float) $product->purchase_price;
                $newPurchasePrice = (float) $item['xml_price'];

                $differenceAmount = round($newPurchasePrice - $previousPrice, 2);
                $differencePct = $previousPrice > 0
                    ? round((($newPurchasePrice - $previousPrice) / $previousPrice) * 100, 4)
                    : 100.0;

                // ── Resolve the new sale price (if admin provided one) ──────
                $newSalePrice = isset($salePrices[$productId])
                    ? (float) $salePrices[$productId]
                    : null;

                // Determine the effective sale price to check constraints.
                // If the admin is also changing sale_price, validate against that;
                // otherwise keep the existing one.
                $effectiveSalePrice = $newSalePrice ?? (float) $product->sale_price;

                // Guard: purchase_price must never exceed sale_price.
                if ($effectiveSalePrice < $newPurchasePrice) {
                    Log::warning('XmlPriceDeviationService: skipped update — new purchase_price would exceed sale_price.', [
                        'product_id' => $productId,
                        'effective_sale' => $effectiveSalePrice,
                        'new_purchase' => $newPurchasePrice,
                    ]);

                    continue;
                }

                // Update purchase price.
                $product->purchase_price = $newPurchasePrice;

                // Update sale price only when the admin explicitly provided a value.
                if ($newSalePrice !== null) {
                    $product->sale_price = $newSalePrice;
                }

                $product->saveQuietly(); // bypass observers/events that might re-validate

                // Write history record for the purchase price change.
                ProductPurchasePriceHistory::create([
                    'product_id' => $productId,
                    'previous_price' => $previousPrice,
                    'new_price' => $newPurchasePrice,
                    'difference_amount' => $differenceAmount,
                    'difference_percentage' => $differencePct,
                    'threshold_percentage' => $thresholdPct,
                    'source' => 'xml_upload',
                    'xml_file_name' => $xmlFileName,
                    'reason' => $reason,
                    'changed_by' => $changedBy,
                ]);

                $count++;
            }
        });

        return $count;
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    /**
     * Load and return a SimpleXMLElement from the uploaded file.
     *
     * @throws \RuntimeException if the file cannot be parsed.
     */
    private function parseXml(UploadedFile $file): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $content = file_get_contents($file->getRealPath());

        if ($content === false) {
            throw new \RuntimeException('No se pudo leer el archivo XML.');
        }

        // Strip UTF-8 BOM if present.
        $content = ltrim($content, "\xEF\xBB\xBF");

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            $errors = collect(libxml_get_errors())
                ->map(fn ($e) => trim($e->message))
                ->implode('; ');

            libxml_clear_errors();

            throw new \RuntimeException('El archivo XML no es válido: '.$errors);
        }

        libxml_clear_errors();

        return $xml;
    }

    /**
     * Extract a normalised list of raw items from any supported XML layout.
     *
     * Returns an array of arrays with keys: code, name, quantity, price.
     */
    private function extractItems(\SimpleXMLElement $xml): array
    {
        $rootTag = strtolower($xml->getName());

        // Variant C — Costa Rica electronic invoice: <invoice><line>
        if (isset($xml->line) || $rootTag === 'invoice' || $rootTag === 'factura') {
            $nodes = $xml->line ?? $xml->Linea ?? $xml->LineaDetalle ?? [];

            return $this->resolveNodes($nodes, 'cr_invoice');
        }

        // Variant B — <products><product>
        if (isset($xml->product)) {
            return $this->resolveNodes($xml->product, 'products');
        }

        // Variant A (default) — <items><item>
        if (isset($xml->item)) {
            return $this->resolveNodes($xml->item, 'items');
        }

        // Last resort: try every direct child as a potential item.
        return $this->resolveNodes($xml->children(), 'generic');
    }

    /**
     * Convert a list of SimpleXMLElement nodes into normalised arrays.
     */
    private function resolveNodes(iterable $nodes, string $layout): array
    {
        $items = [];

        foreach ($nodes as $node) {
            $item = $this->resolveItem($node, $layout);

            if ($item !== null) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Map a single XML node to a normalised item array.
     *
     * Returns null if the price cannot be determined (skip the line).
     *
     * @return array{code:string,name:string,quantity:float,price:float}|null
     */
    private function resolveItem(\SimpleXMLElement $node, string $layout): ?array
    {
        switch ($layout) {
            case 'cr_invoice':
                $code = (string) ($node->CodigoComercial
                                   ?? $node->Codigo
                                   ?? $node->codigo
                                   ?? '');
                $name = (string) ($node->Detalle
                                   ?? $node->detalle
                                   ?? $node->descripcion
                                   ?? '');
                $quantity = (float) ($node->Cantidad
                                   ?? $node->cantidad
                                   ?? 1);
                $price = (float) ($node->PrecioUnitario
                                   ?? $node->precioUnitario
                                   ?? $node->precio
                                   ?? 0);
                break;

            case 'products':
                $code = (string) ($node->sku ?? $node->code ?? $node->codigo ?? '');
                $name = (string) ($node->description ?? $node->name ?? $node->nombre ?? '');
                $quantity = (float) ($node->qty ?? $node->quantity ?? $node->cantidad ?? 1);
                $price = (float) ($node->price ?? $node->unit_price
                                   ?? $node->purchase_price ?? $node->precio ?? 0);
                break;

            case 'items':
            default:
                $code = (string) ($node->code ?? $node->sku ?? $node->codigo ?? '');
                $name = (string) ($node->name ?? $node->nombre ?? $node->description ?? '');
                $quantity = (float) ($node->quantity ?? $node->cantidad ?? $node->qty ?? 1);
                $price = (float) ($node->unit_price ?? $node->purchase_price
                                   ?? $node->price ?? $node->precio ?? 0);
                break;
        }

        if ($price <= 0) {
            return null;
        }

        return compact('code', 'name', 'quantity', 'price');
    }

    /**
     * Find a Product by SKU or generated BK-xxx code.
     *
     * Tries in order:
     *  1. Exact match on products.sku
     *  2. Case-insensitive match on products.sku
     *  3. Match against the auto-generated BK-xxx pattern
     */
    private function findProduct(string $code): ?Product
    {
        if ($code === '') {
            return null;
        }

        // 1. Exact match on products.sku
        $product = Product::query()->where('sku', $code)->first();
        if ($product) {
            return $product;
        }

        // 2. Case-insensitive match on products.sku
        $product = Product::query()
            ->whereRaw('LOWER(TRIM(sku)) = ?', [strtolower(trim($code))])
            ->first();
        if ($product) {
            return $product;
        }

        // 3. BK-{product_id} auto-generated pattern
        //    Examples: BK-1, BK-42, bk-7 (case-insensitive)
        if (preg_match('/^BK-(\d+)$/i', $code, $matches)) {
            $productId = (int) $matches[1];
            if ($productId > 0) {
                return Product::find($productId);
            }

            return null; // BK-0 → not found
        }

        // 4. Fallback: exact name match (case-insensitive, trimmed).
        //    Only used when the supplier omits SKU but sends the product name.
        //    Skipped if the name is ambiguous (more than one product matches).
        $matches = Product::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($code))])
            ->limit(2)
            ->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }
}
