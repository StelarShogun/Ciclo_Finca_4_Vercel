<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\XmlPriceDeviationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

/**
 * XmlPriceDeviationController
 *
 * Handles the full XML price-deviation workflow from inside the
 * supplier-orders (pedidos de proveedor) module.
 *
 * Routes expected in web.php (add inside the admin auth group):
 *
 *   GET  /supplier-orders/xml-deviation
 *        → showUploadForm()   — renders the file-upload view
 *
 *   POST /supplier-orders/xml-deviation/analyse
 *        → analyse()          — parses XML, stores results in session,
 *                               redirects to review view
 *
 *   GET  /supplier-orders/xml-deviation/review
 *        → review()           — shows the comparison table
 *
 *   POST /supplier-orders/xml-deviation/apply
 *        → apply()            — persists chosen updates, writes history
 */
class XmlPriceDeviationController extends Controller
{
    /** Session key used to pass analysis results between requests. */
    private const SESSION_KEY = 'xml_price_deviation_analysis';

    public function __construct(
        private readonly XmlPriceDeviationService $service
    ) {}

    // ─── 1. Upload form ───────────────────────────────────────────────────────

    public function showUploadForm()
    {
        return Inertia::render('Admin/SupplierOrders/XmlUpload');
    }

    // ─── 2. Parse & redirect to review ───────────────────────────────────────

    public function analyse(Request $request)
    {
        $request->validate([
            'xml_file' => ['required', 'file', 'mimes:xml,text', 'max:5120'],
            'threshold' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'xml_file.required' => 'Debe seleccionar un archivo XML.',
            'xml_file.mimes' => 'El archivo debe ser de tipo XML.',
            'xml_file.max' => 'El archivo no puede superar los 5 MB.',
            'threshold.required' => 'Debe indicar el umbral de desvío.',
            'threshold.min' => 'El umbral no puede ser negativo.',
            'threshold.max' => 'El umbral no puede superar el 100%.',
        ]);

        try {
            $analysis = $this->service->analyse(
                file: $request->file('xml_file'),
                thresholdPct: (float) $request->input('threshold', 10)
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['xml_file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('XmlPriceDeviationController@analyse failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'xml_file' => 'Ocurrió un error al procesar el archivo. Verifique que el formato sea correcto.',
            ]);
        }

        if (empty($analysis['items'])) {
            return back()->withInput()->withErrors([
                'xml_file' => 'No se encontraron productos en el archivo XML.',
            ]);
        }

        Session::put(self::SESSION_KEY, $analysis);

        return redirect()->route('admin.supplier-orders.xml-deviation.review');
    }

    // ─── 3. Review table ──────────────────────────────────────────────────────

    public function review()
    {
        $analysis = Session::get(self::SESSION_KEY);

        if (! $analysis) {
            return redirect()
                ->route('admin.supplier-orders.xml-deviation.upload')
                ->withErrors(['xml_file' => 'La sesión de análisis expiró. Por favor, cargue el XML nuevamente.']);
        }

        return Inertia::render('Admin/SupplierOrders/XmlReview', [
            'analysis' => $analysis,
        ]);
    }

    // ─── 4. Apply confirmed updates ───────────────────────────────────────────

    /**
     * POST /supplier-orders/xml-deviation/apply
     *
     * New fields accepted from the form:
     *
     *   updates[]         — product_ids whose purchase_price should be updated
     *   sale_prices[id]   — optional new sale_price per product_id, keyed by id
     *                        e.g. sale_prices[42]=15800.00
     *                        If the field is empty / absent → sale_price is NOT changed.
     *   reason            — optional free-text note
     */
    public function apply(Request $request)
    {
        $request->validate([
            'updates' => ['present', 'array'],
            'updates.*' => ['integer', 'min:1'],
            'sale_prices' => ['nullable', 'array'],
            'sale_prices.*' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $analysis = Session::get(self::SESSION_KEY);

        if (! $analysis) {
            return redirect()
                ->route('admin.supplier-orders.xml-deviation.upload')
                ->withErrors(['xml_file' => 'La sesión de análisis expiró. Por favor, cargue el XML nuevamente.']);
        }

        $selectedIds = array_map('intval', $request->input('updates', []));

        // Build the sale_prices map: [product_id (int) => new_sale_price (float|null)]
        // Only keep entries that actually have a non-empty numeric value.
        $salePricesRaw = $request->input('sale_prices', []);
        $salePrices = [];

        foreach ($salePricesRaw as $productId => $value) {
            $productId = (int) $productId;
            if ($productId > 0 && $value !== null && $value !== '') {
                $salePrices[$productId] = (float) $value;
            }
        }

        // Filter items: must be found in DB and ticked by admin.
        $toUpdate = collect($analysis['items'])
            ->filter(fn ($item) => $item['found'] &&
                ! is_null($item['product_id']) &&
                in_array((int) $item['product_id'], $selectedIds, true)
            )
            ->values()
            ->all();

        $count = 0;

        if (! empty($toUpdate)) {
            $count = $this->service->applyUpdates(
                updates: $toUpdate,
                thresholdPct: (float) $analysis['threshold_percentage'],
                xmlFileName: $analysis['file_name'],
                reason: $request->input('reason'),
                changedBy: (int) auth('admin')->id(),
                salePrices: $salePrices,      // ← new
            );

            $this->logAudit($analysis['file_name'], $count, $selectedIds, $salePrices);
        }

        Session::forget(self::SESSION_KEY);

        $message = $count > 0
            ? "Se actualizó el precio de compra de {$count} producto(s) correctamente."
            : 'No se realizaron cambios. Todos los precios seleccionados se mantuvieron.';

        return redirect()
            ->route('admin.supplier-orders.index')
            ->with('status', $message);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function logAudit(string $fileName, int $count, array $selectedIds, array $salePrices): void
    {
        try {
            app(AuditLogger::class)->logAdminAction(
                'xml_price_deviation_apply',
                'supplier_orders',
                "Actualización de precios desde XML: {$fileName}. Productos actualizados: {$count}.",
                [
                    'xml_file_name' => $fileName,
                    'updated_count' => $count,
                    'selected_ids' => $selectedIds,
                    'sale_price_updates' => $salePrices,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('XmlPriceDeviationController: audit log write failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
