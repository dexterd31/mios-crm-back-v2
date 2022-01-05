<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationAttatchmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications_attatchment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notifications_id')
                  ->nullable(false)
                  ->constrained('notifications');

            $table->longText('static_atachment')
                  ->nullable()
                  ->comment('Nombre del archivo que se va a enviar siempre');

            $table->json('dinamic_atachment')
                  ->nullable()
                  ->comment('Objeto donde se almacenan los campos que conforman el nombre del archivo a enviar en el adjunto.');

            $table->longText('route_atachment');

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
        Schema::dropIfExists('notifications_attatchment');
    }
}
