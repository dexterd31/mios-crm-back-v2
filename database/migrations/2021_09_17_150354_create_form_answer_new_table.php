<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormAnswerNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_answer_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms');
            $table->foreignId('channel_id')->constrained('channels');
            $table->unsignedBigInteger('client_id');
            $table->json('structure_answer');
            $table->unsignedBigInteger('client_new_id')->default(0); 
            $table->json('form_answer_index_data')->nullable();
            $table->string('tipification_time')->nullable();
            $table->unsignedBigInteger('rrhh_id')->default(0); 
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
        Schema::dropIfExists('form_answer_new');
    }
}
