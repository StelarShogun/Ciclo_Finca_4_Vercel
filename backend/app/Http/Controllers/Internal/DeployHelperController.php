<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Shared\Security\SensitiveDataMasker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

final class DeployHelperController extends Controller
{
    public function migrations(Request $request): JsonResponse
    {
        $this->authorizeDeployHelper($request);

        return $this->runArtisan('migrate', ['--force' => true], 'deploy_helper_migrate');
    }

    public function seeders(Request $request, ?string $class = null): JsonResponse
    {
        $this->authorizeDeployHelper($request);

        if ($class !== null && $class !== '' && ! preg_match('/^Database\\\\Seeders\\\\[A-Za-z0-9_]+$/', $class)) {
            return response()->json(['ok' => false, 'message' => 'Seeder no válido.'], 400);
        }

        $params = ['--force' => true];
        if ($class) {
            $params['--class'] = $class;
        }

        return $this->runArtisan('db:seed', $params, 'deploy_helper_seed', ['class' => $class]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     */
    private function runArtisan(string $command, array $params, string $event, array $context = []): JsonResponse
    {
        try {
            $exitCode = Artisan::call($command, $params);
            $output = Artisan::output();

            if ($exitCode !== 0) {
                Log::error($event.'_failed', $context + [
                    'exit_code' => $exitCode,
                    'output_length' => mb_strlen($output),
                ]);

                return response()->json(['ok' => false, 'message' => 'No fue posible ejecutar la operación.'], 500);
            }

            return response()->json(['ok' => true, 'message' => 'Operación ejecutada.']);
        } catch (\Throwable $exception) {
            Log::error($event.'_exception', SensitiveDataMasker::exceptionContext($exception, $context));

            return response()->json(['ok' => false, 'message' => 'No fue posible ejecutar la operación.'], 500);
        }
    }

    private function authorizeDeployHelper(Request $request): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }

        $secret = (string) config('app.deploy_secret', '');
        if ($secret === '') {
            abort(404);
        }

        $provided = (string) ($request->header('X-Deploy-Secret') ?: $request->header('X-Internal-Key'));
        if (! hash_equals($secret, $provided)) {
            abort(404);
        }
    }
}
