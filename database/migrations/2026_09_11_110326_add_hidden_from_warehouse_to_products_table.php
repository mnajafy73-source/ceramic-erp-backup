<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'hidden_from_warehouse')) {
                $table->boolean('hidden_from_warehouse')->default(false)->after('hidden_from_all_stocks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'hidden_from_warehouse')) {
                $table->dropColumn('hidden_from_warehouse');
            }
        });
    }
};