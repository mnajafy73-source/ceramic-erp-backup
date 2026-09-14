<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InventoryChangeLog;
use App\Models\Packaging;
use App\Models\WarehouseInventory;

echo "═══════════════════════════════════════════════\n";
echo "  ۱. تعداد کل لاگ‌ها: " . InventoryChangeLog::count() . "\n";
echo "═══════════════════════════════════════════════\n\n";

echo "  ۲. آخرین ۵ لاگ:\n";
$logs = InventoryChangeLog::orderBy('id', 'desc')->limit(5)->get();
foreach ($logs as $log) {
    echo "  ────────────────────────────\n";
    echo "  id: {$log->id}\n";
    echo "  loggable_type: {$log->loggable_type}\n";
    echo "  loggable_id: {$log->loggable_id}\n";
    echo "  field: {$log->field}\n";
    echo "  old: {$log->old_value} → new: {$log->new_value}\n";
    echo "  mode: {$log->mode}\n";
    echo "  created_at: {$log->created_at}\n";
}

echo "\n═══════════════════════════════════════════════\n";
echo "  ۳. تست query برای Packaging (id=4):\n";
echo "═══════════════════════════════════════════════\n";

$className = Packaging::class;
echo "  دنبال این کلاس: {$className}\n";
echo "  دنبال این id: 4\n";

$found = InventoryChangeLog::where('loggable_type', $className)
    ->where('loggable_id', 4)
    ->orderBy('created_at', 'desc')
    ->get();

echo "  تعداد نتیجه: " . $found->count() . "\n";

foreach ($found as $log) {
    echo "  ────────────────────────────\n";
    echo "  field: {$log->field}\n";
    echo "  old: {$log->old_value} → new: {$log->new_value}\n";
}

echo "\n═══════════════════════════════════════════════\n";
echo "  ۴. تست query برای WarehouseInventory (id=11):\n";
echo "═══════════════════════════════════════════════\n";

$whClass = WarehouseInventory::class;
echo "  دنبال این کلاس: {$whClass}\n";
echo "  دنبال این id: 11\n";

$found2 = InventoryChangeLog::where('loggable_type', $whClass)
    ->where('loggable_id', 11)
    ->orderBy('created_at', 'desc')
    ->get();

echo "  تعداد نتیجه: " . $found2->count() . "\n";

foreach ($found2 as $log) {
    echo "  ────────────────────────────\n";
    echo "  field: {$log->field}\n";
    echo "  old: {$log->old_value} → new: {$log->new_value}\n";
}