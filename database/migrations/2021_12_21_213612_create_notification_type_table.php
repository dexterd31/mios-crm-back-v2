<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications_type', function (Blueprint $table) {
            $table->id();

            $table->string('notification_name',255)
                  ->nullable(false)
                  ->comment('Nombre del tipo de la notificación');

            $table->integer('state')
                  ->nullable(false)
                  ->default(1)
                  ->comment('Estado del Tipo de notificacion 0= inactivo 1 = activo');

            $table->bigInteger('rrhh_id')
                  ->nullable(false)
                  ->comment('Id de la persona que crea la notificación');

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
        Schema::dropIfExists('notifications_type');
    }
}
