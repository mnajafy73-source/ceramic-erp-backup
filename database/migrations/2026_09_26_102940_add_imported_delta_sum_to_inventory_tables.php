<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'warehouse_inventories',
            'raw_inventories',
            'glaze1300_inventories',
            'shoulder_inventories',
            'waste_mum_inventories',
            'wax_inventories',
            'raw_materials',
            'packagings',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'imported_delta_sum')) {
                Schema::table($table, function (Blueprint $t) {
                    // ✅ مقدار پیش‌فرض 0
                    $t->decimal('imported_delta_sum', 20, 4)->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'warehouse_inventories',
            'raw_inventories',
            'glaze1300_inventories',
            'shoulder_inventories',
            'waste_mum_inventories',
            'wax_inventories',
            'raw_materials',
            'packagings',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'imported_delta_sum')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('imported_delta_sum');
                });
            }
        }
    }
};