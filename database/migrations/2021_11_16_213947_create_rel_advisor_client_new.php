<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelAdvisorClientNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rel_advisor_client_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_new_id')->nullable(false)->constrained('client_news');
            $table->bigInteger('rrhh_id',false,true);
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
        Schema::dropIfExists('rel_advisor_client_new');
    }
}
