<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'unpackaged_baseline_unpackaged')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('unpackaged_baseline_unpackaged', 20, 4)
                    ->default(0)
                    ->after('unpackaged_baseline_packaged');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'unpackaged_baseline_unpackaged')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('unpackaged_baseline_unpackaged');
            });
        }
    }
};