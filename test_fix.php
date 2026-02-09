<?php
use Illuminate\Http\Request;
use App\Models\Lock;
use App\Http\Controllers\LockController;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock Request
$lock = Lock::first();
if (!$lock)
    die("No lock found");

$controller = new LockController();

// We can't easily call storeCode directly because it expects a Request and redirects.
// Instead, let's manually replicate the logic to verify the fix works conceptually,
// or just trust the code change if we are confident.
// Better: Create a LockCode manually using the FIXED logic and see if DB saves it right.
// Actually, I can use the same logic as the controller:

$validated = [
    'start_date' => '2026-05-20',
    'end_date' => '2026-05-21'
];

$startDate = \Carbon\Carbon::parse($validated['start_date'])->setTime(15, 0, 0);
$endDate = \Carbon\Carbon::parse($validated['end_date'])->setTime(11, 0, 0);

echo "Start: " . $startDate->toDateTimeString() . "\n";
echo "End: " . $endDate->toDateTimeString() . "\n";

// Verify Model creation
$code = \App\Models\LockCode::create([
    'lock_id' => $lock->id,
    'tuya_password_id' => 'test_' . rand(1000, 9999),
    'name' => 'Test Fix',
    'pin' => '1234567',
    'start_date' => $startDate,
    'end_date' => $endDate,
]);

$code->refresh();
echo "Stored Start: " . $code->start_date . "\n";
echo "Stored End: " . $code->end_date . "\n";

// Cleanup
$code->delete();
