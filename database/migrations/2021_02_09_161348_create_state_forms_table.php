<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStateFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('state_forms', function (Blueprint $table) {
            $table->id();
         /*    $table->unsignedBigInteger('form_answer_id')->nullable();
            $table->unsignedBigInteger('form_type_id')->nullable(); */
            $table->boolean('approval');
            $table->text('observation');
            $table->datetime('date_update');
           
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
        Schema::dropIfExists('state_forms');
    }
}
