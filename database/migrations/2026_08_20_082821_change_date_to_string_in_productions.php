<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->string('date')->change(); // تغییر به string
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->date('date')->change(); // بازگشت به date
        });
    }
};