<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shoulder_records', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('day');
            $table->string('name')->nullable();
            $table->string('product_name');
            $table->integer('carton_count')->default(0);
            $table->integer('per_carton')->default(0);
            $table->integer('total')->default(0);
            $table->integer('shoulder')->default(0);
            $table->timestamps();

            $table->index('product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shoulder_records');
    }
};