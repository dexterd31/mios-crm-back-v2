<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_new_id')->comment('Id del cliente');
            $table->foreignId('tag_id')->comment('Id_de la etiqueta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clients_tags');
    }
}
