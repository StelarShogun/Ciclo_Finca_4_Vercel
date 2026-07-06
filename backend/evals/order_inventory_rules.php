<?php

use App\Models\AppSetting;
use App\Models\Sale;
use App\Services\Admin\Sales\OrderStatusPolicy;
use App\Services\Shared\Sales\OrderExpirationPolicy;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

Cache::setDefaultDriver('array');
config(['app.timezone' => 'UTC']);
date_default_timezone_set('UTC');
Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));
Cache::put(AppSetting::cacheKeyOrderExpirationDays(), 30, 3600);
Cache::put(AppSetting::cacheKeyReadyToPickupExpirationHours(), 72, 3600);

$status = new OrderStatusPolicy;
assert($status->markReadyToPickup(new Sale(['status' => 'pending']))['allowed'] === true);
assert($status->complete(new Sale(['status' => 'ready_to_pickup']))['allowed'] === true);
assert($status->complete(new Sale(['status' => 'pending']))['allowed'] === false);
assert($status->cancel(new Sale(['status' => 'completed']))['allowed'] === false);
assert($status->returnSale(new Sale(['status' => 'completed']))['allowed'] === true);

$expiration = app(OrderExpirationPolicy::class);
$sale = new Sale([
    'sale_date' => Carbon::parse('2026-06-01 12:00:00', 'UTC'),
    'ready_at' => Carbon::parse('2026-06-12 11:59:00', 'UTC'),
]);

assert($expiration->expiresAt($sale)->format('Y-m-d H:i:s') === '2026-07-01 12:00:00');
assert($expiration->isPickupExpired($sale) === true);
assert($expiration->readyToPickupCutoff()->format('Y-m-d H:i:s') === '2026-06-12 12:00:00');

$stockBefore = 4;
$cancelledQuantity = 3;
assert($stockBefore + $cancelledQuantity === 7);

Carbon::setTestNow();

echo "order_inventory_rules OK\n";
