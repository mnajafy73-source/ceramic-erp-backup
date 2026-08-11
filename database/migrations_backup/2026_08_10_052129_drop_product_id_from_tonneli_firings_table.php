<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tonneli_firings', function (Blueprint $table) {
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('tonneli_firings', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable();
        });
    }
};