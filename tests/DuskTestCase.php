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
    protected const LOCAL_CHROMEDRIVER_URL = 'http://localhost:9515';

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
        $driverUrl = env('DUSK_DRIVER_URL');

        return is_string($driverUrl)
            && $driverUrl !== ''
            && $driverUrl !== self::LOCAL_CHROMEDRIVER_URL;
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

        $driverUrl = $_ENV['DUSK_DRIVER_URL'] ?? $_SERVER['DUSK_DRIVER_URL'] ?? 'http://localhost:9515';

        $options = (new ChromeOptions)->addArguments($arguments->all());

        if (! str_contains($driverUrl, '/wd/hub')) {
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
}
