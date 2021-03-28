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
            $table->string('name');
            $table->string('permissions');
            $table->string('filters');
            $table->boolean('approval');
            $table->boolean('status');
            $table->text('observation');      
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
