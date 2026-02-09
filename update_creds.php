<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lock;

$deviceId = 'bf6520bfd42e18f4d8n9to';
$clientId = 'rgkg4vgr5rjcgmq7sxcg';
$clientSecret = 'cf771c4d219b41b6b1b1852e5d8b83b2';

$lock = Lock::where('device_id', $deviceId)->first();

if (!$lock) {
    echo "Lock not found.\n";
    exit(1);
}

$building = $lock->apartment ? $lock->apartment->building : $lock->building;

if (!$building) {
    echo "Building not found.\n";
    exit(1);
}

$building->update([
    'tuya_client_id' => $clientId,
    'tuya_client_secret' => $clientSecret,
]);

echo "Updated credentials for building: {$building->name}\n";
