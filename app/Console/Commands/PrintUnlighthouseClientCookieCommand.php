<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PrintUnlighthouseClientCookieCommand extends Command
{
    protected $signature = 'unlighthouse:client-cookie {gmail? : Client gmail (default: darwinn990@gmail.com)}';

    protected $description = 'Print session cookie for Unlighthouse client scans (local APP_ENV only)';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command only runs when APP_ENV=local.');

            return self::FAILURE;
        }

        $gmail = (string) ($this->argument('gmail') ?? 'darwinn990@gmail.com');
        $client = Client::query()->where('gmail', $gmail)->where('active', true)->first();

        if ($client === null) {
            $this->error("Active client not found: {$gmail}");

            return self::FAILURE;
        }

        Session::start();
        Auth::guard('clients')->login($client);
        Session::save();
        $this->ensureSessionFileReadableByWebServer();

        $name = config('session.cookie');
        $value = encrypt(Session::getId());
        $cookie = "{$name}={$value}";

        $this->line($cookie);
        $this->newLine();
        $this->comment('Add to .env.unlighthouse.local:');
        $this->line("UNLIGHTHOUSE_CLIENT_COOKIE={$cookie}");

        return self::SUCCESS;
    }

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
