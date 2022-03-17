<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTraysLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('traffic_trays_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('traffic_tray_id')->references('id')->on('traffic_trays_config');
            $table->foreignId('form_answer_id')->references('id')->on('form_answers');
            $table->json('data')->comment('contiene los datos que se almacenan en el log');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('traffic_trays_log');
    }
}
