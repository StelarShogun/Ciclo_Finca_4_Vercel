<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\CatalogImportJobRequest;
use App\Http\Requests\Internal\MediaConversionsJobRequest;
use App\Jobs\GenerateCatalogImportMediaConversionsJob;
use App\Jobs\RunCatalogImportJob;
use App\Services\Admin\Images\MissingProductMediaConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

final class VercelController extends Controller
{
    public function scheduler(Request $request): JsonResponse
    {
        $this->authorizeInternal($request);

        return $this->runArtisan('schedule:run');
    }

    public function catalogImport(CatalogImportJobRequest $request): JsonResponse
    {
        $this->authorizeInternal($request);

        $payload = $request->validated();

        $startedAt = hrtime(true);

        app()->call([
            new RunCatalogImportJob(
                importId: $payload['importId'],
                adminId: (int) $payload['adminId'],
                storedPath: $payload['storedPath'],
                originalName: $payload['originalName'],
                disk: $payload['disk'] ?? 'local',
            ),
            'handle',
        ]);

        return response()->json([
            'ok' => true,
            'job' => 'catalog-import',
            'status' => 'done',
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ]);
    }

    public function mediaConversions(MediaConversionsJobRequest $request, MissingProductMediaConversionService $service): JsonResponse
    {
        $this->authorizeInternal($request);

        $payload = $request->validated();

        $startedAt = hrtime(true);
        $ids = isset($payload['mediaIds'])
            ? array_map('intval', $payload['mediaIds'])
            : $service->mediaIdsMissingConversions(null, (int) ($payload['limit'] ?? 50));

        $result = (new GenerateCatalogImportMediaConversionsJob($ids))->handle($service);

        return response()->json([
            'ok' => true,
            'job' => 'media-conversions',
            'status' => 'done',
            'processed' => count($result),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ]);
    }

    public function orderMaintenance(Request $request): JsonResponse
    {
        $this->authorizeInternal($request);

        $commands = [
            'sales:delete-expired',
            'sales:send-expiry-reminders',
            'orders:cancel-expired-ready',
        ];

        $results = [];

        foreach ($commands as $command) {
            $results[$command] = $this->runArtisan($command)->getData(true);
        }

        return response()->json([
            'ok' => true,
            'job' => 'order-maintenance',
            'status' => 'done',
            'results' => $results,
        ]);
    }

    private function runArtisan(string $command): JsonResponse
    {
        $startedAt = hrtime(true);
        $exitCode = Artisan::call($command);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            Log::error('internal_vercel_artisan_failed', [
                'command' => $command,
                'exit_code' => $exitCode,
                'output_length' => mb_strlen($output),
            ]);
        }

        return response()->json([
            'ok' => $exitCode === 0,
            'job' => $command,
            'status' => $exitCode === 0 ? 'done' : 'failed',
            'exit_code' => $exitCode,
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ], $exitCode === 0 ? 200 : 500);
    }

    private function authorizeInternal(Request $request): void
    {
        $secret = (string) config('app.deploy_secret', '');
        $providedHeader = (string) ($request->header('X-Deploy-Secret') ?: $request->header('X-Internal-Key'));

        if ($secret !== '' && hash_equals($secret, $providedHeader)) {
            return;
        }

        abort(404);
    }
}
