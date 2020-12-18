<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; 
use Zareismail\Shaghool\Helper;

class AddUnitIdToShaghoolResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shaghool_resources', function (Blueprint $table) { 
            $table->foreignId('unit_id');    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shaghool_resources', function (Blueprint $table) {    
            $table->dropColumn(['unit_id']);   
        });
    }
}
