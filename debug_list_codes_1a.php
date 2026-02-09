<?php

use App\Models\Lock;
use App\Services\TuyaApiService;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Find Lock "1A" with Building
$lock = Lock::with('building')->where('name', 'LIKE', '%1A%')->first();

if (!$lock) {
    echo "Lock '1A' NOT FOUND in database.\n";
    exit(1);
}

// Try to get building via Apartment
if (!$lock->building && $lock->apartment_id) {
    $lock->load('apartment.building');
    if ($lock->apartment && $lock->apartment->building) {
        $lock->building = $lock->apartment->building;
    }
}

if (!$lock->building) {
    $fallbackBuilding = \App\Models\Building::whereNotNull('tuya_client_id')->first();
    if ($fallbackBuilding) {
        $lock->building = $fallbackBuilding;
    }
}

// 2. Get Codes from Tuya
try {
    $tuya = new TuyaApiService($lock->building);
    $response = $tuya->getTempPasswords($lock->device_id);

    // Filter for "tolaba"
    if (isset($response['list'])) {
        $found = false;
        foreach ($response['list'] as $code) {
            $name = $code['name'] ?? 'Unknown';
            if (stripos($name, 'tolaba') !== false) {
                echo "\n!!! FOUND STUCK CODE IN TUYA API !!!\n";
                echo "Name: " . $name . "\n";
                echo "ID: " . $code['id'] . "\n";
                echo "Status: " . ($code['status'] ?? 'Unknown') . "\n";
                $found = true;
            }
        }
        if (!$found) {
            echo "\nCONFIRMED: 'tolaba' is NOT present in Tuya API response.\n";
        }
    } else {
        echo "No 'list' in response.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
