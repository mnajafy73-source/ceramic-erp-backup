<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('glaze_1300_inventories') && !Schema::hasColumn('glaze_1300_inventories', 'imported_delta_sum')) {
            Schema::table('glaze_1300_inventories', function (Blueprint $t) {
                $t->decimal('imported_delta_sum', 20, 4)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('glaze_1300_inventories', 'imported_delta_sum')) {
            Schema::table('glaze_1300_inventories', function (Blueprint $t) {
                $t->dropColumn('imported_delta_sum');
            });
        }
    }
};