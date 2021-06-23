<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormAnswerMiosPhonesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_answer_mios_phones', function (Blueprint $table) {
            $table->id();
            $table->string('leadId')->nullable();
            $table->string('phoneCustomer')->nullable();
            $table->string('uid')->nullable();
            $table->string('cui')->nullable();
            $table->unsignedBigInteger('form_answer_id')->nullable();
            $table->foreign('form_answer_id')->references('id')->on('form_answers');
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
        Schema::dropIfExists('form_answer_mios_phones');
    }
}
