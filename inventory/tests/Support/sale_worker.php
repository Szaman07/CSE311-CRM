<?php

use App\Domain\DomainConflict;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Contracts\Console\Kernel;

if ($argc !== 7) {
    fwrite(STDERR, "Usage: sale_worker.php user product quantity price key ready-file\n");
    exit(2);
}

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $userId, $productId, $quantity, $price, $key, $readyFile] = $argv;
file_put_contents($readyFile, (string) hrtime(true));

try {
    $sale = $app->make(SaleService::class)->record(
        User::findOrFail((int) $userId),
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
