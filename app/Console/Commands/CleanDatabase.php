<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean the database by removing test products and data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting database cleaning...');

        try {
            DB::beginTransaction();

            // Remove test products (with generic or test names)
            $productsRemoved = Product::where(function ($query) {
                $query->where('name', 'like', '%test%')
                    ->orWhere('name', 'like', '%prueba%')
                    ->orWhere('name', 'like', '%sample%')
                    ->orWhere('name', 'like', '%demo%')
                    ->orWhere('name', 'like', '%lorem%')
                    ->orWhere('name', 'like', '%ipsum%')
                    ->orWhere('name', 'like', '%dolor%')
                    ->orWhere('name', 'like', '%sit%')
                    ->orWhere('name', 'like', '%amet%')
                    ->orWhere('name', 'like', '%consectetur%')
                    ->orWhere('name', 'like', '%adipiscing%')
                    ->orWhere('name', 'like', '%elit%')
                    ->orWhere('name', 'like', '%sed%')
                    ->orWhere('name', 'like', '%do%')
                    ->orWhere('name', 'like', '%eiusmod%')
                    ->orWhere('name', 'like', '%tempor%')
                    ->orWhere('name', 'like', '%incididunt%')
                    ->orWhere('name', 'like', '%labore%')
                    ->orWhere('name', 'like', '%dolore%')
                    ->orWhere('name', 'like', '%magna%')
                    ->orWhere('name', 'like', '%aliqua%')
                    ->orWhere('name', 'like', '%ut%')
                    ->orWhere('name', 'like', '%enim%')
                    ->orWhere('name', 'like', '%ad%')
                    ->orWhere('name', 'like', '%minim%')
                    ->orWhere('name', 'like', '%veniam%')
                    ->orWhere('name', 'like', '%quis%')
                    ->orWhere('name', 'like', '%nostrud%')
                    ->orWhere('name', 'like', '%exercitation%')
                    ->orWhere('name', 'like', '%ullamco%')
                    ->orWhere('name', 'like', '%laboris%')
                    ->orWhere('name', 'like', '%nisi%')
                    ->orWhere('name', 'like', '%aliquip%')
                    ->orWhere('name', 'like', '%ex%')
                    ->orWhere('name', 'like', '%ea%')
                    ->orWhere('name', 'like', '%commodo%')
                    ->orWhere('name', 'like', '%consequat%')
                    ->orWhere('name', 'like', '%duis%')
                    ->orWhere('name', 'like', '%aute%')
                    ->orWhere('name', 'like', '%irure%')
                    ->orWhere('name', 'like', '%reprehenderit%')
                    ->orWhere('name', 'like', '%voluptate%')
                    ->orWhere('name', 'like', '%velit%')
                    ->orWhere('name', 'like', '%esse%')
                    ->orWhere('name', 'like', '%cillum%')
                    ->orWhere('name', 'like', '%fugiat%')
                    ->orWhere('name', 'like', '%nulla%')
                    ->orWhere('name', 'like', '%pariatur%')
                    ->orWhere('name', 'like', '%excepteur%')
                    ->orWhere('name', 'like', '%sint%')
                    ->orWhere('name', 'like', '%occaecat%')
                    ->orWhere('name', 'like', '%cupidatat%')
                    ->orWhere('name', 'like', '%non%')
                    ->orWhere('name', 'like', '%proident%')
                    ->orWhere('name', 'like', '%sunt%')
                    ->orWhere('name', 'like', '%culpa%')
                    ->orWhere('name', 'like', '%qui%')
                    ->orWhere('name', 'like', '%officia%')
                    ->orWhere('name', 'like', '%deserunt%')
                    ->orWhere('name', 'like', '%mollit%')
                    ->orWhere('name', 'like', '%anim%')
                    ->orWhere('name', 'like', '%id%')
                    ->orWhere('name', 'like', '%est%')
                    ->orWhere('name', 'like', '%laborum%');
            })->delete();

            $this->info("✅ Removed {$productsRemoved} test products");

            // Clean related tables that may have test data
            try {
                $historyRemoved = DB::table('inventory_history')->where('reason', 'like', '%test%')->delete();
                $this->info("✅ Removed {$historyRemoved} test history records");
            } catch (\Exception $e) {
                $this->warn('⚠️  Table inventory_history not found, continuing...');
            }

            // Verify that main categories and suppliers are intact
            $categoriesCount = Category::count();
            $suppliersCount = Supplier::count();

            $this->info('📊 Current database state:');
            $this->info("   - Categories: {$categoriesCount}");
            $this->info("   - Suppliers: {$suppliersCount}");
            $this->info('   - Remaining products: '.Product::count());

            DB::commit();
            $this->info('🎉 Cleaning completed successfully');

        } catch (\Exception $e) {
            DB::rollback();
            $this->error('❌ Error during cleaning: '.$e->getMessage());
            $this->error('❌ Error durante la limpieza: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
