<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zareismail\Shaghool\Helper;

class CreateShaghoolReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shaghool_reports', function (Blueprint $table) {
            $table->id(); 
            $table->auth();  
            $table->foreignId('percapita_id')->constrained('shaghool_per_capitas');   
            $table->timestamp('target_date');
            $table->integer('value')->nullable(); 
            $table->integer('balance')->default(0); 
            $table->details(); 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shaghool_reports');
    }
}
