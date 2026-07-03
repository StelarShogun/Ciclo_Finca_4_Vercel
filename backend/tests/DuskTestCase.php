<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (static::runningInSail() || static::usesRemoteWebDriver()) {
            return;
        }

        static::startChromeDriver(['--port=9515']);
    }

    /**
     * Resolve the WebDriver endpoint for Dusk.
     */
    protected static function duskDriverUrl(): string
    {
        foreach (['DUSK_DRIVER_URL'] as $key) {
            if (! empty($_ENV[$key])) {
                return $_ENV[$key];
            }

            if (! empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }

            $fromEnv = getenv($key);

            if ($fromEnv !== false && $fromEnv !== '') {
                return $fromEnv;
            }
        }

        $envPath = dirname(__DIR__).'/.env';

        if (is_readable($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');

                if ($name === 'DUSK_DRIVER_URL') {
                    return trim($value, " \t\n\r\0\x0B\"'");
                }
            }
        }

        return 'http://localhost:9515';
    }

    /**
     * Determine if tests should talk to a remote Selenium server.
     */
    protected static function usesRemoteWebDriver(): bool
    {
        return str_ends_with(static::duskDriverUrl(), '/wd/hub');
    }

    /**
     * Create the Remote WebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $arguments = collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        });

        if (file_exists('/.dockerenv')) {
            $arguments = $arguments->merge([
                '--no-sandbox',
                '--disable-dev-shm-usage',
            ]);
        }

        $options = (new ChromeOptions)->addArguments($arguments->all());

        $driverUrl = static::duskDriverUrl();

        if (! static::usesRemoteWebDriver()) {
            foreach (['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'] as $chromeBinary) {
                if (is_executable($chromeBinary)) {
                    $options->setBinary($chromeBinary);
                    break;
                }
            }
        }

        return RemoteWebDriver::create(
            $driverUrl,
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }

    /**
     * Bootstrap the Laravel application for browser tests.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Fill a React/Inertia controlled input so state updates before submit.
     */
    protected function fillControlledInput(Browser $browser, string $selector, string $value): Browser
    {
        $selectorJson = json_encode($selector, JSON_THROW_ON_ERROR);
        $valueJson = json_encode($value, JSON_THROW_ON_ERROR);

        $browser->script(<<<JS
            const el = document.querySelector({$selectorJson});
            if (!el) {
                throw new Error('Missing element: ' + {$selectorJson});
            }

            const proto = el instanceof HTMLTextAreaElement
                ? window.HTMLTextAreaElement.prototype
                : window.HTMLInputElement.prototype;
            const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;

            if (setter) {
                setter.call(el, {$valueJson});
            } else {
                el.value = {$valueJson};
            }

            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
            JS);

        return $browser;
    }
}
