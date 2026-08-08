<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packaging_purchases', function (Blueprint $table) {
            $table->decimal('total_transport_cost', 15, 2)->default(0)->after('supplier');
        });
    }

    public function down()
    {
        Schema::table('packaging_purchases', function (Blueprint $table) {
            $table->dropColumn('total_transport_cost');
        });
    }
};