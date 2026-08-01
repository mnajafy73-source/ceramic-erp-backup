<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informal_sales', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('number');
            $table->date('date');
            $table->string('customer_name');
            $table->decimal('total_price', 15, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->unique(['year', 'number']);
        });

        Schema::create('informal_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('informal_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informal_sale_products');
        Schema::dropIfExists('informal_sales');
    }
};