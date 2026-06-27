<?php

namespace App\Http\Controllers\Admin\Suppliers;

use App\Actions\Admin\Suppliers\AnalyzeXmlPriceDeviation;
use App\Actions\Admin\Suppliers\ApplyXmlPriceDeviation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\AnalyzeXmlPriceDeviationRequest;
use App\Http\Requests\Admin\Suppliers\ApplyXmlPriceDeviationRequest;
use App\Services\Admin\Suppliers\XmlPriceDeviationStorage;
use App\Services\Shared\Security\SensitiveDataMasker;
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
        private readonly XmlPriceDeviationStorage $storage,
    ) {}

    // ─── 1. Upload form ───────────────────────────────────────────────────────

    public function showUploadForm()
    {
        return Inertia::render('Admin/SupplierOrders/XmlUpload');
    }

    // ─── 2. Parse & redirect to review ───────────────────────────────────────

    public function analyse(AnalyzeXmlPriceDeviationRequest $request, AnalyzeXmlPriceDeviation $action)
    {
        try {
            $analysisId = $action->handle(
                adminId: (int) auth('admin')->id(),
                file: $request->file('xml_file'),
                thresholdPct: (float) $request->input('threshold', 10),
            );
        } catch (\RuntimeException $e) {
            Log::warning('XmlPriceDeviationController@analyse rejected XML.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => auth('admin')->id(),
            ]));

            return back()->withInput()->withErrors([
                'xml_file' => 'No fue posible analizar el XML. Verifique que el archivo tenga productos válidos.',
            ]);
        } catch (\Throwable $e) {
            Log::error('XmlPriceDeviationController@analyse failed.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => auth('admin')->id(),
            ]));

            return back()->withInput()->withErrors([
                'xml_file' => 'Ocurrió un error al procesar el archivo. Verifique que el formato sea correcto.',
            ]);
        }

        Session::put(self::SESSION_KEY, $analysisId);

        return redirect()->route('admin.supplier-orders.xml-deviation.review');
    }

    // ─── 3. Review table ──────────────────────────────────────────────────────

    public function review()
    {
        $analysis = $this->currentAnalysis();

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
    public function apply(ApplyXmlPriceDeviationRequest $request, ApplyXmlPriceDeviation $action)
    {
        $analysis = $this->currentAnalysis();

        if (! $analysis) {
            return redirect()
                ->route('admin.supplier-orders.xml-deviation.upload')
                ->withErrors(['xml_file' => 'La sesión de análisis expiró. Por favor, cargue el XML nuevamente.']);
        }

        try {
            $count = $action->handle($analysis, $request->validated(), (int) auth('admin')->id());
        } catch (\Throwable $e) {
            Log::error('XmlPriceDeviationController@apply failed.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => auth('admin')->id(),
                'analysis_id' => $this->currentAnalysisId(),
            ]));

            return back()->withErrors([
                'updates' => 'No fue posible aplicar los cambios seleccionados. Inténtelo nuevamente.',
            ]);
        }

        $this->storage->forget((int) auth('admin')->id(), $this->currentAnalysisId());
        Session::forget(self::SESSION_KEY);

        $message = $count > 0
            ? "Se actualizó el precio de compra de {$count} producto(s) correctamente."
            : 'No se realizaron cambios. Todos los precios seleccionados se mantuvieron.';

        return redirect()
            ->route('admin.supplier-orders.index')
            ->with('status', $message);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function currentAnalysis(): ?array
    {
        $legacyAnalysis = Session::get(self::SESSION_KEY);
        if (is_array($legacyAnalysis)) {
            return $legacyAnalysis;
        }

        return $this->storage->get((int) auth('admin')->id(), $this->currentAnalysisId());
    }

    private function currentAnalysisId(): ?string
    {
        $value = Session::get(self::SESSION_KEY);

        return is_string($value) ? $value : null;
    }
}
