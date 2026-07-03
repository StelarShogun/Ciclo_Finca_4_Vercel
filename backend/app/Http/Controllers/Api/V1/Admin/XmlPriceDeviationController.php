<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\Suppliers\AnalyzeXmlPriceDeviation;
use App\Actions\Admin\Suppliers\ApplyXmlPriceDeviation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Suppliers\AnalyzeXmlPriceDeviationRequest;
use App\Http\Requests\Admin\Suppliers\ApplyXmlPriceDeviationRequest;
use App\Services\Admin\Suppliers\XmlPriceDeviationStorage;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Desviación de precios por XML para el SPA Next. Equivalente JSON del
 * web Admin\Suppliers\XmlPriceDeviationController: en vez de sesión, el
 * analyse devuelve analysis_id (cache 30 min via XmlPriceDeviationStorage)
 * y el apply lo recibe de vuelta.
 */
final class XmlPriceDeviationController extends Controller
{
    public function __construct(
        private readonly XmlPriceDeviationStorage $storage,
    ) {}

    public function analyse(AnalyzeXmlPriceDeviationRequest $request, AnalyzeXmlPriceDeviation $action): JsonResponse
    {
        $adminId = (int) auth('admin')->id();

        try {
            $analysisId = $action->handle(
                adminId: $adminId,
                file: $request->file('xml_file'),
                thresholdPct: (float) $request->input('threshold', 10),
            );
        } catch (\RuntimeException $e) {
            Log::warning('Api XmlPriceDeviation@analyse rejected XML.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => $adminId,
            ]));

            return response()->json([
                'message' => 'No fue posible analizar el XML. Verifique que el archivo tenga productos válidos.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Api XmlPriceDeviation@analyse failed.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => $adminId,
            ]));

            return response()->json([
                'message' => 'Ocurrió un error al procesar el archivo. Verifique que el formato sea correcto.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'analysisId' => $analysisId,
                'analysis' => $this->storage->get($adminId, $analysisId),
            ],
        ]);
    }

    public function apply(ApplyXmlPriceDeviationRequest $request, ApplyXmlPriceDeviation $action): JsonResponse
    {
        $adminId = (int) auth('admin')->id();
        $analysisId = (string) $request->input('analysis_id', '');
        $analysis = $this->storage->get($adminId, $analysisId);

        if (! $analysis) {
            return response()->json([
                'message' => 'La sesión de análisis expiró. Por favor, cargue el XML nuevamente.',
            ], 410);
        }

        try {
            $count = $action->handle($analysis, $request->validated(), $adminId);
        } catch (\Throwable $e) {
            Log::error('Api XmlPriceDeviation@apply failed.', SensitiveDataMasker::exceptionContext($e, [
                'admin_id' => $adminId,
                'analysis_id' => $analysisId,
            ]));

            return response()->json([
                'message' => 'No fue posible aplicar los cambios seleccionados. Inténtelo nuevamente.',
            ], 500);
        }

        $this->storage->forget($adminId, $analysisId);

        return response()->json([
            'data' => ['updated' => $count],
            'message' => $count > 0
                ? "Se actualizó el precio de compra de {$count} producto(s) correctamente."
                : 'No se realizaron cambios. Todos los precios seleccionados se mantuvieron.',
        ]);
    }
}
