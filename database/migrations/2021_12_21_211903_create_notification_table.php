<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('form_id')
                  ->nullable(false)
                  ->constrained('forms');

            $table->foreignId('notification_type')
                  ->nullable(false)
                  ->constrained('notifications_type');

            $table->text('name')
                  ->nullable(false);

            $table->json('activators')
                  ->nullable(false)
                  ->comment('Json con los ids valores que activan el envio de la notificacion');

            $table->string('subject',255)
                  ->comment('asunto del correo');

            $table->json('to')
                ->comment('número o correos a los que serán enviados');

            $table->longText('template_to_send')
                  ->nullable(false)
                  ->comment('Html o plantilla de texto para ser enviado');

            $table->bigInteger('rrhh_id')
                  ->nullable(false)
                  ->comment('Id de la persona que crea la notificación');

            $table->integer('state')
                  ->nullable(false)
                  ->default(1)
                  ->comment('Estadodel Tipo de notificacion o= inactivo 1 = activo');

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
        Schema::dropIfExists('notifications');
    }
}
