<?php

namespace App\Console\Commands\Dev;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDemoData extends Command
{
    protected $signature = 'dev:clean-demo-data {--force : Execute deletion without interactive confirmation}';

    protected $description = 'Remove demo/test products by explicit name or SKU patterns (local/testing only)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Este comando solo puede ejecutarse en local/testing.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Esto eliminará productos demo/test. ¿Continuar?')) {
            $this->info('Operación cancelada.');

            return self::SUCCESS;
        }

        $this->info('🧹 Starting demo data cleaning...');

        try {
            DB::beginTransaction();

            $productsRemoved = Product::query()
                ->where(function ($query) {
                    $query->where('name', 'like', '[TEST]%')
                        ->orWhere('name', 'like', '[DEMO]%')
                        ->orWhere('name', 'like', '[FAKE]%')
                        ->orWhere('sku', 'like', 'TEST-%')
                        ->orWhere('sku', 'like', 'DEMO-%')
                        ->orWhere('sku', 'like', 'FAKE-%')
                        ->orWhere(function ($nameQuery) {
                            foreach (['test', 'prueba', 'sample', 'demo', 'lorem', 'ipsum', 'dummy', 'fake'] as $pattern) {
                                $nameQuery->orWhere('name', 'like', '%'.$pattern.'%');
                            }
                        });
                })
                ->delete();

            $this->info("✅ Removed {$productsRemoved} demo/test products");

            try {
                $historyRemoved = DB::table('inventory_history')->where('reason', 'like', '%test%')->delete();
                $this->info("✅ Removed {$historyRemoved} test history records");
            } catch (\Exception $e) {
                $this->warn('⚠️  Table inventory_history not found, continuing...');
            }

            $this->info('   - Remaining products: '.Product::count());

            DB::commit();
            $this->info('🎉 Cleaning completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during cleaning: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
