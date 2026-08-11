<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            // با استفاده از `after` ترتیب ستون‌ها را رعایت کنید
            $table->integer('year')->nullable()->after('date');
            $table->integer('month')->nullable()->after('year');
            $table->integer('day')->nullable()->after('month');
        });
    }

    public function down()
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day']);
        });
    }
};