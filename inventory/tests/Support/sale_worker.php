<?php

use App\Domain\DomainConflict;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\DatabaseSafety;

if (! in_array($argc, [7, 8], true)) {
    fwrite(STDERR, "Usage: sale_worker.php user target quantity price key ready-file [sale|restock|cancel]\n");
    exit(2);
}

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DatabaseSafety::assertSafe($app);

[$script, $userId, $productId, $quantity, $price, $key, $readyFile] = $argv;
$operation = $argv[7] ?? 'sale';
if (! in_array($operation, ['sale', 'restock', 'cancel'], true)) {
    exit(2);
}
file_put_contents($readyFile, (string) hrtime(true));

try {
    $actor = User::findOrFail((int) $userId);
    if ($operation === 'cancel') {
        $sale = $app->make(SaleService::class)->cancel($actor, Sale::findOrFail((int) $productId), $key);
        echo json_encode(['result' => 'cancelled', 'sale_id' => (string) $sale->id], JSON_THROW_ON_ERROR);
        exit(0);
    }
    if ($operation === 'restock') {
        $movement = $app->make(StockService::class)->change($actor, Product::findOrFail((int) $productId), 'restock', (int) $quantity, 'Concurrent restock', $key);
        echo json_encode(['result' => 'restocked', 'movement_id' => (string) $movement->id], JSON_THROW_ON_ERROR);
        exit(0);
    }
    $sale = $app->make(SaleService::class)->record(
        $actor,
        null,
        [['product_id' => (int) $productId, 'quantity' => (int) $quantity, 'expected_unit_price' => $price]],
        $key,
    );
    echo json_encode(['result' => 'completed', 'sale_id' => (string) $sale->id], JSON_THROW_ON_ERROR);
} catch (DomainConflict $e) {
    echo json_encode(['result' => 'conflict', 'code' => $e->errorCode], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    echo json_encode(['result' => 'error', 'class' => $e::class, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
    exit(1);
}
