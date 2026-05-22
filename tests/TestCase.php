<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Env;
use Illuminate\Support\HtmlString;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cuando PHPUnit fija DB_DATABASE=:memory:, Laravel puede seguir leyendo DB_CONNECTION=mysql
     * desde .env.testing vía el repositorio de Env (no desde getenv). Eso deja mysql+:memory: y rompe migraciones.
     */
    public function createApplication(): Application
    {
        $this->alignEnvForInMemorySqlite();

        return parent::createApplication();
    }

    private function alignEnvForInMemorySqlite(): void
    {
        $database = getenv('DB_DATABASE');
        if ($database === false) {
            $database = $_ENV['DB_DATABASE'] ?? null;
        }

        if ($database !== ':memory:') {
            return;
        }

        $repository = Env::getRepository();

        foreach (['DB_CONNECTION', 'DB_DATABASE', 'DB_HOST'] as $key) {
            $repository->clear($key);
        }

        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel 11+ uses PreventRequestForgery; JSON feature tests do not send CSRF tokens.
        $this->withoutMiddleware(PreventRequestForgery::class);

        // phpunit.mysql.xml forces APP_TIMEZONE=UTC; keep config in sync for date assertions.
        $timezone = Env::get('APP_TIMEZONE');
        if (is_string($timezone) && $timezone !== '') {
            config(['app.timezone' => $timezone]);
        }

        // Feature tests run without `npm run build`; @vite() would read public/build/manifest.json.
        $this->app->instance(Vite::class, new class extends Vite
        {
            public function __invoke($entrypoints, $buildDirectory = null): HtmlString
            {
                return new HtmlString('');
            }
        });
    }
}
