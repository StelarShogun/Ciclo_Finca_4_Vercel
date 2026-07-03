<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PrintUnlighthouseAdminCookieCommand extends Command
{
    protected $signature = 'unlighthouse:admin-cookie {gmail? : Admin gmail (default: admin@cicloperez.com)}';

    protected $description = 'Print session cookie for Unlighthouse admin scans (local APP_ENV only)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command only runs when APP_ENV=local.');

            return self::FAILURE;
        }

        $gmail = (string) ($this->argument('gmail') ?? 'admin@cicloperez.com');
        $admin = AdminUser::query()->where('gmail', $gmail)->first();

        if ($admin === null) {
            $this->error("Admin user not found: {$gmail}");

            return self::FAILURE;
        }

        Session::start();
        Auth::guard('admin')->login($admin);
        Session::save();
        $this->ensureSessionFileReadableByWebServer();

        $name = config('session.cookie');
        // Browsers send the encrypted cookie value (EncryptCookies middleware), not the raw session id.
        $value = encrypt(Session::getId());
        $cookie = "{$name}={$value}";

        $this->line($cookie);
        $this->newLine();
        $this->comment('Add to .env.unlighthouse.local:');
        $this->line("UNLIGHTHOUSE_ADMIN_COOKIE={$cookie}");

        return self::SUCCESS;
    }

    /** Artisan often runs as root in Docker; Apache reads sessions as www-data. */
    private function ensureSessionFileReadableByWebServer(): void
    {
        if (config('session.driver') !== 'file') {
            return;
        }

        $path = config('session.files').'/'.Session::getId();
        if (! is_file($path)) {
            return;
        }

        @chmod($path, 0644);
        if (function_exists('posix_getpwuid')) {
            $www = posix_getpwnam('www-data');
            if ($www !== false) {
                @chown($path, $www['uid']);
                @chgrp($path, $www['gid']);
            }
        }
    }
}
