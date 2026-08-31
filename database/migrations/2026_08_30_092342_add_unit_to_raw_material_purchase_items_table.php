<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_material_purchase_items', function (Blueprint $table) {
            $table->string('unit', 10)->default('kg')->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('raw_material_purchase_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};