<?php

use App\Models\Lock;
use App\Services\TuyaApiService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Listing Temp Passwords ===\n";

// Use the lock we know has codes
$deviceId = 'bf6520bfd42e18f4d8n9to';
$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    die("Lock not found\n");
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;
$tuya = new TuyaApiService($building);

try {
    $data = $tuya->getTempPasswords($deviceId);
    // echo "Getting details for ID 676016114...\n";
    // $data = $tuya->getTempPassword($deviceId, "676016114");
    echo json_encode($data, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
