<?php

use App\Models\Lock;
use App\Services\TuyaApiService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Updating Temp Password ===\n";

$deviceId = 'bf6520bfd42e18f4d8n9to';
$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    die("Lock not found\n");
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;
$tuya = new TuyaApiService($building);

// Update with a code ID that definitely exists (manually verify this ID from UI or DB)
// Using '675818814' from previous contexts if it wasn't deleted, or pick a new one.
// Let's list codes first to find a valid one
try {
    $codes = $tuya->getTempPasswords($deviceId);
    if (empty($codes['list'])) {
        die("No codes found to update. Create one first.\n");
    }
    $targetCode = $codes['list'][0];
    $codeId = $targetCode['id'];
    echo "Found target code: $codeId (" . $targetCode['name'] . ")\n";

    echo "Attempting to update...\n";
    // Simulate updating name and dates
    $newName = "Updated " . rand(100, 999);
    $newStart = time() + 3600; // +1 hour
    $newEnd = time() + 86400; // +1 day
    $pin = (string) rand(1000000, 9999999); // 7 digits random

    $result = $tuya->updateTempPassword($deviceId, $codeId, $pin, $newName, $newStart, $newEnd);
    echo "Update Result: " . ($result ? 'TRUE' : 'FALSE') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
