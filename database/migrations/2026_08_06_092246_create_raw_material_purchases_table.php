<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('raw_material_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->string('supplier')->nullable();
            $table->decimal('total_transport_cost', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('raw_material_purchases');
    }
};