<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lock;
use App\Services\TuyaApiService;
use Carbon\Carbon;

echo "=== Creating Temp Password Test ===\n";

$deviceId = 'bf6520bfd42e18f4d8n9to';
$pin = '6543216';  // 7 digits as required by X7 lock

$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    echo "[ERROR] Lock not found in DB.\n";
    exit;
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;

if (!$building) {
    echo "[ERROR] No Building assigned.\n";
    exit;
}

echo "Using Building: {$building->name}\n";
echo "Client ID: {$building->tuya_client_id}\n";

try {
    $tuya = new TuyaApiService($building);

    // Create password valid for 20 minutes from now
    $now = Carbon::now();
    // Tuya V2 requires 10-digit SECONDS timestamp
    $start = $now->timestamp;
    $end = $now->copy()->addMinutes(20)->timestamp;

    echo "Creating PIN: $pin\n";
    echo "Start (seconds): " . $start . " (" . date('Y-m-d H:i:s', $start) . ")\n";
    echo "End (seconds): " . $end . " (" . date('Y-m-d H:i:s', $end) . ")\n";

    // Note: Tuya might expect milliseconds. Service uses seconds but converts internally? 
    // Let's check TuyaApiService.php again. 
    // createTempPassword accepts `int $effectiveTime` and `int $invalidTime`.
    // Inside it passes them as is to the body.
    // getAccessToken uses `time() * 1000`.
    // Tuya API V2 usually expects UNIX timestamp (seconds) for effective_time.
    // Let's rely on the service logic.

    $result = $tuya->createTempPassword(
        $deviceId,
        $pin,
        $start,
        $end,
        "Test Ivan " . rand(100, 999)
    );

    echo "SUCCESS!\n";
    print_r($result);

} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
