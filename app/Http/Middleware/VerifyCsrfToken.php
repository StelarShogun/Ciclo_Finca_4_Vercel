<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Las rutas de ventas ahora requieren CSRF token para mayor seguridad
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, \Closure $next)
    {
        // #region agent log
        $logPath = base_path('.cursor/debug.log');
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logData = [
            'location' => 'VerifyCsrfToken.php:handle',
            'message' => 'CSRF middleware - request check',
            'data' => [
                'method' => $request->method(),
                'uri' => $request->getRequestUri(),
                'hasToken' => $request->has('_token'),
                'hasXCsrfHeader' => $request->hasHeader('X-CSRF-TOKEN'),
                'sessionToken' => $request->session()->token() ?? 'no-session',
                'inputToken' => $request->input('_token') ?? 'no-input-token',
                'headerToken' => $request->header('X-CSRF-TOKEN') ?? 'no-header-token',
            ],
            'timestamp' => time() * 1000,
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B'
        ];
        @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion
        
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $e) {
            // #region agent log
            $errorLog = [
                'location' => 'VerifyCsrfToken.php:handle',
                'message' => 'CSRF Token Mismatch Exception',
                'data' => [
                    'method' => $request->method(),
                    'uri' => $request->getRequestUri(),
                    'sessionToken' => $request->session()->token() ?? 'no-session',
                    'inputToken' => $request->input('_token') ?? 'no-input-token',
                    'headerToken' => $request->header('X-CSRF-TOKEN') ?? 'no-header-token',
                    'exception' => get_class($e),
                ],
                'timestamp' => time() * 1000,
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ];
            @file_put_contents($logPath, json_encode($errorLog) . "\n", FILE_APPEND | LOCK_EX);
            // #endregion
            
            throw $e;
        }
    }
}