<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('number');
            $table->date('date');
            $table->string('customer_name');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();

            $table->unique(['year', 'number']);
        });

        Schema::create('invoice_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->integer('box')->default(0);
            $table->integer('layer')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
        Schema::dropIfExists('invoices');
    }
};