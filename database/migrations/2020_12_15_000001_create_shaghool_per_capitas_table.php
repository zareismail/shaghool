<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Zareismail\Shaghool\Helper;

class CreateShaghoolPerCapitasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shaghool_per_capitas', function (Blueprint $table) {
            $table->id(); 
            $table->auth(); 
            $table->morphs('measurable'); 
            $table->foreignId('resource_id')->constrained('shaghool_resources'); 
            $table->enum('period', array_keys(Helper::periods()))->default(Helper::MONTHLY);
            $table->tinyInteger('due')->default(1);  
            $table->tinyInteger('duration')->default(1);  
            $table->timestamp('start_date')->nullable();
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
        Schema::dropIfExists('shaghool_per_capitas');
    }
}
