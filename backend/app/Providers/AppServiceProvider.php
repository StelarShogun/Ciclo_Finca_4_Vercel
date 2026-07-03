<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\ClassificationDimension;
use App\Models\ClassificationValue;
use App\Models\FavoriteProduct;
use App\Policies\ClassificationPolicy;
use App\Policies\FavoritePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SupplierOrderPolicy;
use App\Policies\UserProfilePolicy;
use App\Services\Shared\Filesystem\VercelBlobAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Notifications\DatabaseNotification;
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
        // En Vercel el bundle es de solo lectura y Flysystem crea el root del
        // disco local al instanciarlo: cualquier getUrl()/exists() sobre media
        // con disk local reventaba. /tmp es el único path escribible en lambda.
        if (config('vercel.enabled')) {
            config([
                'filesystems.disks.local.root' => '/tmp/storage/app/private',
                'filesystems.disks.public.root' => '/tmp/storage/app/public',
            ]);
        }
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

        Gate::policy(FavoriteProduct::class, FavoritePolicy::class);
        Gate::policy(ClassificationDimension::class, ClassificationPolicy::class);
        Gate::policy(ClassificationValue::class, ClassificationPolicy::class);
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);

        Gate::define('reports.view', [ReportPolicy::class, 'view']);
        Gate::define('reports.export', [ReportPolicy::class, 'export']);
        Gate::define('invoices.view', [InvoicePolicy::class, 'view']);
        Gate::define('invoices.export', [InvoicePolicy::class, 'export']);
        Gate::define('profile.view', [UserProfilePolicy::class, 'view']);
        Gate::define('profile.update', [UserProfilePolicy::class, 'update']);
        Gate::define('supplier-orders.receive', [SupplierOrderPolicy::class, 'receive']);
        Gate::define('supplier-orders.close-partial', [SupplierOrderPolicy::class, 'closePartial']);
    }
}
