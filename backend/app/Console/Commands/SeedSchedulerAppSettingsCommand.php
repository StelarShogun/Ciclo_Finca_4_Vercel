<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedSchedulerAppSettingsCommand extends Command
{
    protected $signature = 'cf4:seed-scheduler-app-settings
                            {--dry-run : List keys that would be inserted without writing}';

    protected $description = 'Insert CF4-163 scheduler monitoring rows into app_settings (production-safe: never overwrites existing keys).';

    public function handle(): int
    {
        $keys = AppSetting::schedulerMonitoringKeys();
        $dryRun = (bool) $this->option('dry-run');

        $toInsert = [];
        foreach ($keys as $key) {
            if (DB::table('app_settings')->where('key', $key)->exists()) {
                continue;
            }
            $toInsert[] = $key;
        }

        if ($toInsert === []) {
            $this->info('All scheduler app_settings keys already exist. Nothing to do.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run — would insert '.count($toInsert).' key(s):');
            foreach ($toInsert as $key) {
                $this->line('  - '.$key);
            }

            return self::SUCCESS;
        }

        $now = now();
        foreach ($toInsert as $key) {
            DB::table('app_settings')->insert([
                'key' => $key,
                'value' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->info('Inserted '.count($toInsert).' scheduler app_settings key(s).');
        $this->line('Heartbeat and per-command timestamps will update when the scheduler runs on Render.');

        return self::SUCCESS;
    }
}
