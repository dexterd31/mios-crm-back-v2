<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_questions', function (Blueprint $table) {
            $table->id();
            $table->json('relationship');
            $table->tinyInteger('status')->default(1);
            $table->foreignId('form_id')->constrained('forms');
            $table->foreignId('api_id')->constrained('api_connections');
            
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
        Schema::dropIfExists('api_question');
    }
}
