<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateCatalogImportMediaConversionsJob;
use App\Jobs\RunCatalogImportJob;
use App\Services\Admin\Images\MissingProductMediaConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

final class VercelController extends Controller
{
    public function scheduler(Request $request): JsonResponse
    {
        $this->authorizeInternal($request, allowVercelCron: true);

        return $this->runArtisan('schedule:run');
    }

    public function catalogImport(Request $request): JsonResponse
    {
        $this->authorizeInternal($request);

        $payload = $request->validate([
            'importId' => ['required', 'string'],
            'adminId' => ['required', 'integer'],
            'storedPath' => ['required', 'string'],
            'originalName' => ['required', 'string'],
            'disk' => ['nullable', 'string'],
        ]);

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

    public function mediaConversions(Request $request, MissingProductMediaConversionService $service): JsonResponse
    {
        $this->authorizeInternal($request);

        $payload = $request->validate([
            'mediaIds' => ['nullable', 'array'],
            'mediaIds.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $startedAt = hrtime(true);
        $ids = isset($payload['mediaIds'])
            ? array_map('intval', $payload['mediaIds'])
            : $service->mediaIdsMissingConversions(null, (int) ($payload['limit'] ?? 50));

        $result = (new GenerateCatalogImportMediaConversionsJob($ids))->handle($service);

        return response()->json([
            'ok' => true,
            'job' => 'media-conversions',
            'status' => 'done',
            'media_ids' => $ids,
            'result' => $result,
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

        return response()->json([
            'ok' => $exitCode === 0,
            'job' => $command,
            'status' => $exitCode === 0 ? 'done' : 'failed',
            'exit_code' => $exitCode,
            'output' => trim(Artisan::output()),
            'duration_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
        ], $exitCode === 0 ? 200 : 500);
    }

    private function authorizeInternal(Request $request, bool $allowVercelCron = false): void
    {
        $secret = (string) config('app.deploy_secret', '');
        // Aceptamos el secreto por query (?key=) o por header reenviado por QStash
        // (X-Internal-Key), ya que QStash puede no preservar el query del destino.
        $provided = (string) $request->query('key', '');
        $providedHeader = (string) $request->header('X-Internal-Key', '');

        if ($secret !== '' && (hash_equals($secret, $provided) || hash_equals($secret, $providedHeader))) {
            return;
        }

        if (
            $allowVercelCron
            && config('vercel.enabled')
            && str_contains((string) $request->userAgent(), 'vercel-cron/1.0')
        ) {
            return;
        }

        abort(404);
    }
}
