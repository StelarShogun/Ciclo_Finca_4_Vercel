<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Long-cache hashed Vite build assets in production (immutable filenames).
 */
final class CacheStaticBuildAssets
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! app()->environment('production')) {
            return $response;
        }

        $path = $request->path();
        if (! str_starts_with($path, 'build/')) {
            return $response;
        }

        if ($response->isSuccessful() || $response->isRedirection()) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }
}
