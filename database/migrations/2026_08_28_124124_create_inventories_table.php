<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->integer('box')->default(0);
            $table->integer('layer')->default(0);
            $table->integer('pallet')->default(0);
            $table->timestamps();

            // هر محصول فقط یک رکورد موجودی می‌تواند داشته باشد
            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};