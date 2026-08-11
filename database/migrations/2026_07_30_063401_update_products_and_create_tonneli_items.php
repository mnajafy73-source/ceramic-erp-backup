<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فقط جدول tonneli_firing_items را ایجاد کن
        Schema::create('tonneli_firing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tonneli_firing_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('input_quantity', 15, 2)->default(0);
            $table->decimal('output_quantity', 15, 2)->default(0);
            $table->boolean('is_packaged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tonneli_firing_items');
    }
};