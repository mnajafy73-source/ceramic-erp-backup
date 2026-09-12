<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packagings', function (Blueprint $table) {
            if (!Schema::hasColumn('packagings', 'baseline_consumed')) {
                $table->integer('baseline_consumed')->nullable()->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('packagings', function (Blueprint $table) {
            if (Schema::hasColumn('packagings', 'baseline_consumed')) {
                $table->dropColumn('baseline_consumed');
            }
        });
    }
};