<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->date('date')->nullable();
            $table->timestamps();

            // هر محصول فقط یک بار می‌تواند موجودی اولیه داشته باشد
            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_inventories');
    }
};