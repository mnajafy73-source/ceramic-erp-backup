<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shuttle_firings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'packaging']);
            $table->string('firing_number')->nullable();
            $table->enum('firing_subtype', ['mum', 'glaze'])->nullable();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('output_quantity', 10, 2)->nullable();
            $table->boolean('is_packaged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shuttle_firings');
    }
};