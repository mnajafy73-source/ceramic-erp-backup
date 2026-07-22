<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tonneli_firings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('input_quantity', 10, 2)->default(0);
            $table->decimal('output_quantity', 10, 2)->default(0);
            $table->boolean('is_packaged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tonneli_firings');
    }
};