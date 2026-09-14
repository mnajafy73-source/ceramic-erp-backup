<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id');
            $table->string('field', 50)->nullable();
            $table->decimal('old_value', 15, 2)->default(0);
            $table->decimal('new_value', 15, 2)->default(0);
            $table->string('mode', 20)->default('set');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['loggable_type', 'loggable_id'], 'inv_log_type_id_idx');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_change_logs');
    }
};