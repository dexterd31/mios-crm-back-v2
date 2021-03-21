<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_connections', function (Blueprint $table) {

            $table->id();
            $table->string('name')->nullable();
            $table->string('url');
            $table->string('autorization_type')->nullable();
            $table->string('token')->nullable();
            $table->string('other_autorization_type')->nullable();
            $table->string('other_token')->nullable();
            $table->string('mode');
            $table->json('json_send')->nullable();
            $table->json('json_response');
            $table->tinyInteger('request_type');
            $table->tinyInteger('api_type');
            $table->tinyInteger('status');
            $table->foreignId('form_id')->constrained('forms');

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
        Schema::dropIfExists('api_connections');
    }
}
