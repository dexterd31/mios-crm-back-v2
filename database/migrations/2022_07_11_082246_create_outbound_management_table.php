<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->comment('Id del formulario.');
            $table->string('name')->comment('Nombre de la difusión y/o gestión.');
            $table->string('channel')->comment('Canal de disfusión.');
            $table->json('settings')->comment('Configuraciónes');
            $table->bigInteger('total', false, true)->default(0)->comment('Total difundido y/o enviado');
            $table->enum('status',['Borrador', 'Entregado', ['En proceso...']])->default('Borrador')->comment('Estado de la gestión.');
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
        Schema::dropIfExists('outbound_management');
    }
}
