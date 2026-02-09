<?php

use App\Models\Lock;
use App\Services\TuyaApiService;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Update Endpoints ===\n";

$deviceId = 'bf6520bfd42e18f4d8n9to'; // Hardcoded for this test
$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    die("Lock not found\n");
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;
$tuya = new TuyaApiService($building);

try {
    $codes = $tuya->getTempPasswords($deviceId);
    if (empty($codes['list'])) {
        die("No codes found. Create one first.\n");
    }
    // Try to find code '676119614' or just take the first one
    $targetCode = null;
    foreach ($codes['list'] as $c) {
        if ($c['id'] == '676119614')
            $targetCode = $c;
    }
    if (!$targetCode)
        $targetCode = $codes['list'][0];

    $codeId = $targetCode['id'];
    echo "Target Code: $codeId (" . $targetCode['name'] . ")\n";

    $paths = [
        "/v1.0/devices/{$deviceId}/door-lock/temp-password/{$codeId}", // Singular
        "/v1.0/devices/{$deviceId}/door-lock/temp-passwords/{$codeId}", // Plural
        "/v1.0/smart-lock/devices/{$deviceId}/temp-password/{$codeId}", // Smart-lock prefix
        "/v1.0/smart-lock/devices/{$deviceId}/temp-passwords/{$codeId}", // Smart-lock prefix plural
        "/v1.0/devices/{$deviceId}/door-lock/template/temp-password/{$codeId}", // Template
    ];

    // Prepare valid data for update
    $ticket = $tuya->getPasswordTicket($deviceId);
    $pin = "1234567";
    $encryptedPin = $tuya->encryptPassword($pin, $ticket['ticket_key']);
    $newStart = time() + 3600;
    $newEnd = time() + 86400;

    $body = [
        'password' => $encryptedPin,
        'password_type' => 'ticket',
        'ticket_id' => $ticket['ticket_id'],
        'effective_time' => $newStart,
        'invalid_time' => $newEnd
    ];

    foreach ($paths as $path) {
        echo "Testing PUT $path ... ";

        $result = $tuya->rawRequest('PUT', $path, $body);

        if ($result['success'] ?? false) {
            echo "SUCCESS!\n";
            print_r($result);
            break; // Found it!
        } else {
            echo "FAILED: " . ($result['msg'] ?? 'Unknown') . " (Code: " . ($result['code'] ?? '?') . ")\n";
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
