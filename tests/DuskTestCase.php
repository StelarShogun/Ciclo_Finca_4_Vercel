<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
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
        if (! static::runningInSail() && ! static::usingRemoteDriver()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    protected static function usingRemoteDriver(): bool
    {
        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? $_SERVER['DUSK_DRIVER_URL'] ?? getenv('DUSK_DRIVER_URL');

        return is_string($driverUrl)
            && $driverUrl !== ''
            && $driverUrl !== 'http://localhost:9515';
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

        foreach (['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'] as $chromeBinary) {
            if (is_executable($chromeBinary)) {
                $options->setBinary($chromeBinary);
                break;
            }
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? $_SERVER['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
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
}
