<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_change_logs', function (Blueprint $table) {
            $table->string('source', 50)->nullable()->after('mode');
            $table->text('description')->nullable()->after('source');

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_change_logs', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'description']);
        });
    }
};