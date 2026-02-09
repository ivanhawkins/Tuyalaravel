<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$codes = \App\Models\LockCode::orderBy('id', 'desc')->take(5)->get();
foreach ($codes as $c) {
    echo "ID: " . $c->id . " | Start: " . $c->start_date . " | End: " . $c->end_date . " | Name: " . $c->name . "\n";
}
