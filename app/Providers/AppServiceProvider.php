<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Services\Filesystem\VercelBlobAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Storage::extend('vercel_blob', function ($app, array $config): FilesystemAdapter {
            $adapter = new VercelBlobAdapter(
                token: (string) ($config['token'] ?? ''),
                publicUrl: (string) ($config['url'] ?? ''),
                prefix: (string) ($config['prefix'] ?? ''),
            );

            $laravelConfig = $config;
            unset($laravelConfig['prefix']);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $laravelConfig,
            );
        });

        Gate::define('viewPulse', function ($user = null): bool {
            return auth('admin')->user() instanceof AdminUser;
        });
    }
}
