<?php

use App\Models\Lock;
use App\Services\TuyaApiService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Deleting Temp Password ===\n";

$deviceId = 'bf6520bfd42e18f4d8n9to';
$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    die("Lock not found\n");
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;
$tuya = new TuyaApiService($building);

// ID from previous debug output: 675818814 (manolo cabezabolo)
$codeId = '675818814';

try {
    echo "Attempting to delete code ID: $codeId\n";
    $result = $tuya->deleteTempPassword($deviceId, $codeId);
    echo "Result: " . ($result ? 'TRUE' : 'FALSE') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
