<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // حذف ایندکس قدیمی (اگر وجود داشت)
        DB::statement("DROP INDEX IF EXISTS shuttle_firings_kiln_type_year_month_firing_number_unique");
        
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'firing_number'], 'shuttle_unique_firing');
        });
    }

    public function down()
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropUnique('shuttle_unique_firing');
        });
    }
};