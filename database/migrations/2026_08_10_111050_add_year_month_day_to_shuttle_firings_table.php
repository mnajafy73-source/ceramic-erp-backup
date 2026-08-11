<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            if (!Schema::hasColumn('shuttle_firings', 'year')) {
                $table->integer('year')->nullable()->after('date');
            }
            if (!Schema::hasColumn('shuttle_firings', 'month')) {
                $table->integer('month')->nullable()->after('year');
            }
            if (!Schema::hasColumn('shuttle_firings', 'day')) {
                $table->integer('day')->nullable()->after('month');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day']);
        });
    }
};