<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_makings', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('day');
            $table->string('name')->nullable();
            $table->string('material'); // بالمیل/مواد
            $table->decimal('quantity', 15, 2)->default(0); // مقدار (تعداد/کیلو)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_makings');
    }
};