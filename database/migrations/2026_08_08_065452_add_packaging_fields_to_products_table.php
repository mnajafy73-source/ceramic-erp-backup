<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('carton_packaging_id')->nullable()->constrained('packagings')->nullOnDelete()->after('formula_id');
            $table->foreignId('layer_packaging_id')->nullable()->constrained('packagings')->nullOnDelete()->after('carton_packaging_id');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['carton_packaging_id']);
            $table->dropForeign(['layer_packaging_id']);
            $table->dropColumn(['carton_packaging_id', 'layer_packaging_id']);
        });
    }
};