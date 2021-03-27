<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEscalationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('escalations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('form_id')->constrained('forms');
            $table->json('fields');
            $table->bigInteger('asunto_id');
            $table->bigInteger('estado_id');
            $table->bigInteger('campaign_id');
            $table->boolean('state');
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
        Schema::dropIfExists('scalations');
    }
}
