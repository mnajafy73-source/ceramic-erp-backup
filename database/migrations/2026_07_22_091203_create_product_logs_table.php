<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->enum('firing_process', ['standard', 'multistage']);
            $table->enum('kiln_type', ['tonneli', 'shuttle', 'both']);
            $table->integer('tonneli_feed_rate')->nullable();
            $table->integer('cavities')->default(1);
            $table->integer('per_box')->nullable();
            $table->integer('per_pack')->nullable();
            $table->integer('per_pallet')->nullable();
            $table->string('box_type')->nullable();
            $table->integer('layers_per_box')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('in_production')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};