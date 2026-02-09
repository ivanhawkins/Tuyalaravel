<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lock;
use App\Services\TuyaApiService;
use Illuminate\Support\Facades\Log;

echo "=== Debugging Locks & Tuya API ===\n";

$locks = Lock::with(['apartment.building', 'building'])->get();

if ($locks->isEmpty()) {
    echo "No locks found in database.\n";
    exit;
}

foreach ($locks as $lock) {
    echo "\nLock: {$lock->name} (ID: {$lock->id}, DeviceID: {$lock->device_id})\n";

    $building = $lock->apartment ? $lock->apartment->building : $lock->building;

    if (!$building) {
        echo "[ERROR] No Building assigned (via apartment or direct).\n";
        continue;
    }

    echo "Building: {$building->name} (ID: {$building->id})\n";
    echo "Tuya Client ID: " . substr($building->tuya_client_id ?? 'NULL', 0, 5) . "...\n";

    try {
        echo "Initializing Service...\n";
        $tuya = new TuyaApiService($building);

        echo "Fetching Token...\n";
        $token = $tuya->getAccessToken();
        echo "Token: " . substr($token, 0, 10) . "...\n";

        echo "Fetching Temp Passwords...\n";
        $passwords = $tuya->getTempPasswords($lock->device_id);

        echo "Success! Found " . count($passwords['list'] ?? []) . " passwords.\n";
        foreach ($passwords['list'] ?? [] as $pwd) {
            echo " - {$pwd['name']} (ID: {$pwd['id']}, InvalidTime: " . date('Y-m-d H:i', $pwd['invalid_time'] / 1000) . ")\n";
        }

    } catch (\Exception $e) {
        echo "[ERROR] Tuya API Failure: " . $e->getMessage() . "\n";
    }
}
