<?php

use App\Models\Lock;
use App\Services\TuyaApiService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $lock = Lock::find(4);
    if (!$lock) {
        echo "No lock found.\n";
        exit;
    }

    echo "Testing with lock: " . $lock->name . " (" . $lock->device_id . ")\n";

    $building = $lock->apartment ? $lock->apartment->building : $lock->building;
    if (!$building) {
        echo "No building found for lock.\n";
        exit;
    }

    $tuya = new TuyaApiService($building);
    echo "Service initialized.\n";

    $data = $tuya->getTempPasswords($lock->device_id);
    echo "Tuya API response received.\n";
    print_r($data);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
