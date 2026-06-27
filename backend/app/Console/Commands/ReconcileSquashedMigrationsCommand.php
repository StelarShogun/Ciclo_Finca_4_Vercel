<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReconcileSquashedMigrationsCommand extends Command
{
    protected $signature = 'cf4:reconcile-squashed-migrations
                            {--force : Skip confirmation in production}';

    protected $description = 'Register squashed migration names (0010–0017) when upgrading from legacy dated migrations';

    /** @var list<string> */
    private const MARK_AS_RAN = [
        '0010_price_histories',
        '0011_inventory',
        '0012_audit',
        '0013_catalog',
        '0014_notifications',
        '0015_queue',
        '0017_app_seeds',
    ];

    private const LEGACY_PULSE = '2026_06_06_204454_create_pulse_tables';

    private const SQUASHED_PULSE = '0016_pulse';

    public function handle(): int
    {
        if (! Schema::hasTable('migrations')) {
            $this->warn('No migrations table yet; nothing to reconcile.');

            return self::SUCCESS;
        }

        $legacyCount = (int) DB::table('migrations')
            ->where(function ($query): void {
                $query->where('migration', 'like', '2024\\_%')
                    ->orWhere('migration', 'like', '2026\\_%');
            })
            ->count();

        $squashedPending = collect(self::MARK_AS_RAN)
            ->merge([self::SQUASHED_PULSE])
            ->filter(fn (string $name): bool => ! $this->migrationRecorded($name))
            ->values();

        if ($legacyCount === 0 && $squashedPending->isEmpty()) {
            $this->info('Squashed migrations already reconciled (or fresh install).');

            return self::SUCCESS;
        }

        if ($legacyCount === 0 && $squashedPending->isNotEmpty()) {
            $this->info('No legacy migration rows; pending squashed migrations will run via migrate.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && app()->environment('production')) {
            if (! $this->confirm('Reconcile legacy migration history for squashed files?')) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $batch = ((int) DB::table('migrations')->max('batch')) + 1;
        $marked = 0;

        foreach (self::MARK_AS_RAN as $migration) {
            if ($this->migrationRecorded($migration)) {
                continue;
            }

            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $marked++;
            $this->line("  registered: {$migration}");
        }

        if ($this->shouldMarkPulseAsRan()) {
            DB::table('migrations')->insert([
                'migration' => self::SQUASHED_PULSE,
                'batch' => $batch,
            ]);
            $marked++;
            $this->line('  registered: '.self::SQUASHED_PULSE.' (tables already exist)');
        }

        $removed = DB::table('migrations')
            ->where(function ($query): void {
                $query->where('migration', 'like', '2024\\_%')
                    ->orWhere('migration', 'like', '2026\\_%');
            })
            ->delete();

        $this->info("Reconciliation complete: {$marked} squashed migration(s) registered, {$removed} legacy row(s) removed.");

        if (! $this->migrationRecorded(self::SQUASHED_PULSE) && ! Schema::hasTable('pulse_values')) {
            $this->comment('Run php artisan migrate next to create Pulse tables (0016_pulse).');
        }

        return self::SUCCESS;
    }

    private function migrationRecorded(string $migration): bool
    {
        return DB::table('migrations')->where('migration', $migration)->exists();
    }

    private function shouldMarkPulseAsRan(): bool
    {
        if ($this->migrationRecorded(self::SQUASHED_PULSE)) {
            return false;
        }

        if (Schema::hasTable('pulse_values')) {
            return true;
        }

        return $this->migrationRecorded(self::LEGACY_PULSE);
    }
}
